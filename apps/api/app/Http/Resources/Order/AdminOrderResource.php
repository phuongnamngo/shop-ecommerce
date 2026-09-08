<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;

/** @mixin Order */
final class AdminOrderResource extends OrderResource
{
    /**
     * @return array{customer_id: int|null, id: int, number: string, status: string, currency: string, subtotal: string, discount_total: string, shipping_total: string, tax_total: string, grand_total: string, shipping_address: array{recipient_name: string, phone: string, province_code: string, district_code: string, ward_code: string, address_line: string, postal_code?: string|null}|null, items?: list<OrderItem>, status_history?: list<OrderStatusHistory>, created_at: string|null}
     */
    public function toArray(Request $request): array
    {
        return ['customer_id' => $this->customer_id] + parent::toArray($request);
    }
}
