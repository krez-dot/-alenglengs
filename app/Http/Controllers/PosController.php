<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Services\CheckoutService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        return view('pos.index', [
            'products' => Product::with('category')->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(['senior', 'pwd'])],
            'discount_id_number' => ['nullable', 'string', 'max:50'],
        ], [
            'items.required' => 'No items in cart.',
            'items.min' => 'No items in cart.',
            'items.*.product_id.exists' => 'One of the products in the cart no longer exists.',
            'amount_paid.*' => 'Enter the cash tendered.',
        ]);

        $discountType = $data['discount_type'] ?? 'none';
        $idNumber = isset($data['discount_id_number']) ? trim($data['discount_id_number']) : null;

        if ($discountType !== 'none') {
            if (! $request->boolean('discount_confirmed')) {
                return back()->with('danger', 'Please confirm the Senior Citizen / PWD ID was presented to apply the discount.');
            }
            if ($idNumber === null || $idNumber === '') {
                return back()->with('danger', 'Enter the Senior Citizen / PWD ID number to apply the discount.');
            }
        }

        try {
            $sale = $checkout->checkout($data['items'], $data['amount_paid'], $discountType, $idNumber, $request->user()->id);
        } catch (InsufficientStockException|DomainException $e) {
            return back()->with('danger', 'Sale failed: ' . $e->getMessage());
        }

        return redirect()->route('sales.show', $sale)->with('success', 'Sale completed successfully.');
    }
}
