<?php

namespace App\Http\Resources\Inventory;

use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockItem */
final class StockItemResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     warehouse_id: int,
     *     product_variant_id: int,
     *     qty_on_hand: int,
     *     qty_reserved: int,
     *     available_qty: int,
     *     warehouse: array{id: int, code: string, name: string},
     *     variant: array{id: int, sku: string, product_id: int, product_name: string|null}
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_variant_id' => $this->product_variant_id,
            'qty_on_hand' => $this->qty_on_hand,
            'qty_reserved' => $this->qty_reserved,
            /** @var int */
            'available_qty' => $this->qty_on_hand - $this->qty_reserved,
            'warehouse' => [
                'id' => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ],
            'variant' => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'product_id' => $this->variant->product_id,
                'product_name' => $this->variant->product?->name,
            ],
        ];
    }
}
