<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'number' => $this->number, 'status' => $this->status, 'currency' => $this->currency, 'subtotal' => $this->subtotal, 'discount_total' => $this->discount_total, 'shipping_total' => $this->shipping_total, 'tax_total' => $this->tax_total, 'grand_total' => $this->grand_total, 'shipping_address' => $this->shipping_address_snapshot, 'items' => $this->whenLoaded('items'), 'status_history' => $this->whenLoaded('statusHistories'), 'created_at' => $this->created_at?->toISOString()];
    }
}
