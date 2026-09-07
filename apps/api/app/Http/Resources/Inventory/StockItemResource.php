<?php

namespace App\Http\Resources\Inventory;

use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockItem */
final class StockItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'warehouse_id' => $this->warehouse_id, 'product_variant_id' => $this->product_variant_id, 'qty_on_hand' => $this->qty_on_hand, 'qty_reserved' => $this->qty_reserved, 'available_qty' => $this->qty_on_hand - $this->qty_reserved];
    }
}
