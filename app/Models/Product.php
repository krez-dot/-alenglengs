<?php

namespace App\Models;

use App\Exceptions\InsufficientStockException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'sku', 'name', 'category_id', 'supplier_id',
        'cost_price', 'selling_price', 'stock_quantity', 'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Products at or below their reorder level (replaces Product::lowStock()). */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('stock_quantity');
    }

    /** Keyword / category / supplier filters (replaces Product::search()). */
    public function scopeSearch(Builder $query, ?string $keyword, ?int $categoryId = null, ?int $supplierId = null): Builder
    {
        return $query
            ->when($keyword, fn (Builder $q) => $q->where(
                fn (Builder $w) => $w->where('name', 'like', "%{$keyword}%")->orWhere('sku', 'like', "%{$keyword}%")
            ))
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->when($supplierId, fn (Builder $q) => $q->where('supplier_id', $supplierId));
    }

    /**
     * Deducts stock under a row lock. Call inside a DB transaction
     * (replaces Product::deductStock()).
     */
    public static function deductStock(int $productId, int $quantity): void
    {
        $product = static::query()->lockForUpdate()->find($productId);

        if ($product === null) {
            throw new InsufficientStockException("Product #{$productId} not found.");
        }

        if ($product->stock_quantity < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for {$product->name}. Available: {$product->stock_quantity}, requested: {$quantity}."
            );
        }

        $product->decrement('stock_quantity', $quantity);
    }
}
