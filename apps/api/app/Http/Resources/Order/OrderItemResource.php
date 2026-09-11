<?php

namespace App\Http\Resources\Order;

use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
final class OrderItemResource extends JsonResource
{
    /**
     * @return array{id: int, product_variant_id: int|null, product_id: int|null, product_slug: string|null, sku: string, name: string, qty: int, unit_price: string, line_total: string}
     */
    public function toArray(Request $request): array
    {
        $variant = $this->resolvedVariant();
        $product = $variant?->relationLoaded('product') ? $variant->product : $variant?->product;

        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_id' => $product?->id,
            'product_slug' => $product?->slug,
            'sku' => $this->sku,
            'name' => $this->name,
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'line_total' => $this->line_total,
        ];
    }

    private function resolvedVariant(): ?ProductVariant
    {
        if ($this->relationLoaded('variant')) {
            return $this->variant;
        }

        return $this->variant()->withTrashed()->with('product')->first();
    }
}
