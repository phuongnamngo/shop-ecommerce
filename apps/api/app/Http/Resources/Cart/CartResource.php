<?php

namespace App\Http\Resources\Cart;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
final class CartResource extends JsonResource
{
    /**
     * @return array{id: int, currency: string, items: list<CartItemResource>, subtotal: string}
     */
    public function toArray(Request $request): array
    {
        $items = CartItemResource::collection($this->whenLoaded('items'))->resolve($request);

        return [
            'id' => $this->id,
            'currency' => $this->currency,
            /** @var list<CartItemResource> */
            'items' => $items,
            'subtotal' => number_format($this->items->sum(fn ($item) => (float) $item->unit_price * $item->qty), 2, '.', ''),
        ];
    }
}
