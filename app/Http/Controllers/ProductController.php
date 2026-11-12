<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const SORTABLE = ['name', 'selling_price', 'stock_quantity', 'created_at'];

    public function index(Request $request): View
    {
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'name';
        $dir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $categoryId = $request->integer('category_id') ?: null;
        $supplierId = $request->integer('supplier_id') ?: null;
        $keyword = trim((string) $request->query('q', ''));

        $products = Product::with(['category', 'supplier'])
            ->search($keyword, $categoryId, $supplierId)
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'keyword' => $keyword,
            'categoryId' => $categoryId,
            'supplierId' => $supplierId,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'product' => new Product(['cost_price' => 0, 'selling_price' => 0, 'stock_quantity' => 0, 'reorder_level' => 10]),
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($request->validate($this->rules(), $this->messages()));

        return redirect()->route('products.index')->with('success', 'Product added successfully.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($request->validate($this->rules($product), $this->messages()));

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $product->delete();
        } catch (QueryException $e) {
            return back()->with('danger', 'Cannot delete this product: it appears in past sales.');
        }

        return redirect()->route('products.index')->with('success', 'Product deleted.');
    }

    public function restock(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(
            ['quantity' => ['required', 'integer', 'min:1']],
            ['quantity.*' => 'Enter a valid quantity to restock.']
        );

        DB::transaction(function () use ($product, $data) {
            $product->increment('stock_quantity', $data['quantity']);
            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => 'in',
                'quantity' => $data['quantity'],
                'reason' => 'Manual restock',
            ]);
        });

        return back()->with('success', "Added {$data['quantity']} units to \"{$product->name}\".");
    }

    private function rules(?Product $product = null): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'A product with that SKU already exists.',
            'name.required' => 'Product name is required.',
            'category_id.required' => 'Please select a category.',
            'selling_price.*' => 'Unit price must be a valid non-negative number.',
            'cost_price.*' => 'Cost price must be a valid non-negative number.',
            'stock_quantity.*' => 'Stock quantity must be a whole number.',
            'reorder_level.*' => 'Reorder level must be a whole number.',
        ];
    }
}
