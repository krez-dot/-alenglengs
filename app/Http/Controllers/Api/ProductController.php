<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::with(['category', 'supplier'])
            ->search($data['q'] ?? null, $data['category_id'] ?? null, $data['supplier_id'] ?? null)
            ->orderBy('name')
            ->paginate($data['per_page'] ?? 15);

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'supplier']));
    }

    public function store(Request $request): JsonResponse
    {
        $product = Product::create($this->validated($request));

        return (new ProductResource($product->load(['category', 'supplier'])))
            ->additional(['message' => 'Product created.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $product->update($this->validated($request, $product));

        return new ProductResource($product->load(['category', 'supplier']));
    }

    public function destroy(Product $product)
    {
        try {
            $product->delete();
        } catch (QueryException) {
            return response()->json(['message' => 'This product appears in past sales and cannot be deleted.'], 409);
        }

        return response()->noContent();
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $required = $product === null ? 'required' : 'sometimes';

        return $request->validate([
            'sku' => [$required, 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product?->id)],
            'name' => [$required, 'string', 'max:150'],
            'category_id' => [$required, 'integer', 'exists:categories,id'],
            'supplier_id' => ['sometimes', 'nullable', 'integer', 'exists:suppliers,id'],
            'cost_price' => [$required, 'numeric', 'min:0'],
            'selling_price' => [$required, 'numeric', 'min:0'],
            'stock_quantity' => [$required, 'integer', 'min:0'],
            'reorder_level' => [$required, 'integer', 'min:0'],
        ]);
    }
}
