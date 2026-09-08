<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array{id: int, number: string, status: string, currency: string, subtotal: string, discount_total: string, shipping_total: string, tax_total: string, grand_total: string, shipping_address: array{recipient_name: string, phone: string, province_code: string, district_code: string, ward_code: string, address_line: string, postal_code?: string|null}|null, items?: list<OrderItem>, status_history?: list<array{from_status: string|null, to_status: string, created_at: string|null}>, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'shipping_total' => $this->shipping_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            /** @var array{recipient_name: string, phone: string, province_code: string, district_code: string, ward_code: string, address_line: string, postal_code?: string|null}|null */
            'shipping_address' => $this->shipping_address_snapshot,
            'items' => $this->whenLoaded('items'),
            'status_history' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories->map(fn (OrderStatusHistory $history) => [
                'from_status' => $history->from_status,
                'to_status' => $history->to_status,
                'created_at' => $history->created_at?->toISOString(),
            ])->all()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
