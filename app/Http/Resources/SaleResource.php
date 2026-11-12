<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->reference_no,
            'cashier' => $this->whenLoaded('cashier', fn () => $this->cashier ? [
                'id' => $this->cashier->id,
                'name' => $this->cashier->name,
            ] : null),
            'subtotal_amount' => $this->subtotal_amount,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'change_due' => $this->change_due,
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
