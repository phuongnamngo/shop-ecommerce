<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use App\Models\OrderShipment;
use Illuminate\Http\Request;

/** @mixin Order */
final class AdminOrderResource extends OrderResource
{
    /**
     * @return array{
     *     customer_id: int|null,
     *     id: int,
     *     number: string,
     *     status: string,
     *     currency: string,
     *     subtotal: string,
     *     discount_total: string,
     *     shipping_total: string,
     *     tax_total: string,
     *     grand_total: string,
     *     shipping_address: array{recipient_name: string, phone: string, province_code: string, district_code: string, ward_code: string, address_line: string, postal_code?: string|null}|null,
     *     items?: mixed,
     *     status_history?: list<array{from_status: string|null, to_status: string, created_at: string|null}>,
     *     shipments?: list<array{id: int, tracking_number: string, carrier_code: string|null, status: string}>,
     *     created_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'customer_id' => $this->customer_id,
            'shipments' => $this->whenLoaded('shipments', fn () => $this->shipments->map(fn (OrderShipment $shipment) => [
                'id' => $shipment->id,
                'tracking_number' => $shipment->tracking_number,
                'carrier_code' => $shipment->carrier_code,
                'status' => $shipment->status,
            ])->all()),
        ]);
    }
}
