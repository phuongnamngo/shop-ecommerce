<?php

namespace App\Http\Resources\Promotion;

use App\Models\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FlashSale
 */
class AdminFlashSaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->get();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'sale_price' => $item->sale_price,
                'qty_cap' => $item->qty_cap,
                'qty_sold' => (int) $item->qty_sold,
                'qty_remaining' => $item->qtyRemaining(),
            ])->values()->all(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
