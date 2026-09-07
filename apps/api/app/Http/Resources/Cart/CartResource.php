<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cart */
final class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = CartItemResource::collection($this->whenLoaded('items'))->resolve($request);

        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'items' => $items,
            'subtotal' => number_format($this->items->sum(fn ($item) => (float) $item->unit_price * $item->qty), 2, '.', ''),
        ];
    }
}
