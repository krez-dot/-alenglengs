<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use DomainException;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Creates a sale atomically. Prices and the discount are ALWAYS computed
     * here from the database, never trusted from the browser. Product rows are
     * locked (in id order, to avoid deadlocks) so two cashiers cannot oversell
     * the same stock. Any failure rolls the whole sale back.
     *
     * @param  array<int, array{product_id: int|string, quantity: int|string}>  $items
     *
     * @throws InsufficientStockException
     * @throws DomainException
     */
    public function checkout(
        array $items,
        string|float $amountPaid,
        string $discountType = 'none',
        ?string $discountIdNumber = null,
        ?int $userId = null
    ): Sale {
        $wanted = [];
        foreach ($items as $item) {
            $id = (int) $item['product_id'];
            $wanted[$id] = ($wanted[$id] ?? 0) + (int) $item['quantity'];
        }
        if ($wanted === []) {
            throw new DomainException('No items in cart.');
        }

        $discountType = in_array($discountType, ['senior', 'pwd'], true) ? $discountType : 'none';
        $paidCents = (int) round(((float) $amountPaid) * 100);

        return DB::transaction(function () use ($wanted, $discountType, $discountIdNumber, $paidCents, $userId) {
            $products = Product::whereIn('id', array_keys($wanted))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;
            $lines = [];
            foreach ($wanted as $id => $qty) {
                $product = $products->get($id);
                if ($product === null) {
                    throw new InsufficientStockException("Product #{$id} not found.");
                }
                if ($product->stock_quantity < $qty) {
                    throw new InsufficientStockException(
                        "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}, requested: {$qty}."
                    );
                }

                $unitCents = (int) round(((float) $product->selling_price) * 100);
                $subtotalCents += $unitCents * $qty;
                $lines[] = [$product, $qty, $unitCents];
            }

            // Senior Citizen / PWD discount (RA 9994 / RA 10754), half-up in whole cents.
            $discountCents = $discountType === 'none'
                ? 0
                : intdiv($subtotalCents * Sale::DISCOUNT_PERCENT + 50, 100);
            $totalCents = $subtotalCents - $discountCents;

            if ($paidCents < $totalCents) {
                throw new DomainException('Amount paid is less than the total due.');
            }

            $referenceNo = 'TXN-' . now()->format('YmdHis') . '-' . random_int(100, 999);

            $sale = Sale::create([
                'user_id' => $userId,
                'reference_no' => $referenceNo,
                'subtotal_amount' => $this->money($subtotalCents),
                'discount_type' => $discountType,
                'discount_id_number' => $discountType === 'none' ? null : $discountIdNumber,
                'discount_amount' => $this->money($discountCents),
                'total_amount' => $this->money($totalCents),
                'amount_paid' => $this->money($paidCents),
                'change_due' => $this->money($paidCents - $totalCents),
            ]);

            foreach ($lines as [$product, $qty, $unitCents]) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $this->money($unitCents),
                    'subtotal' => $this->money($unitCents * $qty),
                ]);

                $product->decrement('stock_quantity', $qty);

                StockMovement::create([
                    'product_id' => $product->id,
                    'movement_type' => 'out',
                    'quantity' => $qty,
                    'reason' => "Sale #{$referenceNo}",
                ]);
            }

            return $sale;
        });
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
