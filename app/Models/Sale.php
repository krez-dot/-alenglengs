<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const UPDATED_AT = null;

    /** Senior Citizen / PWD discount rate (RA 9994 / RA 10754). */
    public const DISCOUNT_RATE = 0.20;

    /** Same rate as a whole percent, for exact integer-cent math. */
    public const DISCOUNT_PERCENT = 20;

    protected $fillable = [
        'user_id',
        'reference_no', 'subtotal_amount', 'discount_type', 'discount_id_number',
        'discount_amount', 'total_amount', 'amount_paid', 'change_due',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
