<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\CheckoutService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $sales = Sale::with('cashier')
            ->when($data['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($data['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->paginate($data['per_page'] ?? 15);

        return SaleResource::collection($sales);
    }

    public function show(Sale $sale): SaleResource
    {
        return new SaleResource($sale->load(['cashier', 'items.product']));
    }

    public function store(Request $request, CheckoutService $checkout): SaleResource|JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:senior,pwd'],
            'discount_id_number' => ['nullable', 'string', 'max:50'],
            'discount_confirmed' => ['nullable', 'boolean'],
        ]);

        $discountType = $data['discount_type'] ?? 'none';
        $idNumber = isset($data['discount_id_number']) ? trim($data['discount_id_number']) : null;
        if ($discountType !== 'none' && (! ($data['discount_confirmed'] ?? false) || $idNumber === null || $idNumber === '')) {
            return response()->json([
                'message' => 'Confirm the discount ID was presented and provide its number.',
            ], 422);
        }

        try {
            $sale = $checkout->checkout(
                $data['items'],
                $data['amount_paid'],
                $discountType,
                $idNumber,
                $request->user()->id,
            );
        } catch (InsufficientStockException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return new SaleResource($sale->load(['cashier', 'items.product']));
    }
}
