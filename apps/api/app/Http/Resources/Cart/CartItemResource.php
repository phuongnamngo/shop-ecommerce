<?php

namespace App\Http\Resources\Cart;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartItem */
final class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'line_total' => number_format((float) $this->unit_price * $this->qty, 2, '.', ''),
        ];
    }
}
