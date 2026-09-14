<?php

namespace App\Http\Resources\Order;

use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\PaymentTransaction;
use App\Models\Refund;
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
            'payment' => $this->whenLoaded('paymentTransactions', function () {
                $txn = $this->paymentTransactions->sortByDesc('id')->first();
                if (! $txn instanceof PaymentTransaction) {
                    return null;
                }

                return [
                    'id' => $txn->id,
                    'provider' => $txn->provider,
                    'amount' => number_format((float) $txn->amount, 2, '.', ''),
                    'status' => $txn->status,
                    'provider_txn_id' => $txn->provider_txn_id,
                    'idempotency_key' => $txn->idempotency_key,
                ];
            }),
            'refunds' => $this->whenLoaded('paymentTransactions', fn () => $this->paymentTransactions
                ->flatMap(fn (PaymentTransaction $txn) => $txn->refunds)
                ->sortByDesc('id')
                ->values()
                ->map(fn (Refund $refund) => [
                    'id' => $refund->id,
                    'payment_transaction_id' => $refund->payment_transaction_id,
                    'amount' => number_format((float) $refund->amount, 2, '.', ''),
                    'status' => $refund->status,
                    'reason' => $refund->reason,
                    'provider_refund_id' => $refund->provider_refund_id,
                    'idempotency_key' => $refund->idempotency_key,
                    'requested_by_admin_id' => $refund->requested_by_admin_id,
                    'reviewed_by_admin_id' => $refund->reviewed_by_admin_id,
                    'reviewed_at' => $refund->reviewed_at?->toISOString(),
                    'created_at' => $refund->created_at?->toISOString(),
                ])
                ->all()),
            'shipping_method' => $this->whenLoaded('shippingMethod', fn () => $this->shippingMethod === null ? null : [
                'id' => $this->shippingMethod->id,
                'code' => $this->shippingMethod->code,
            ]),
            'ghn_service_id' => $this->ghn_service_id,
        ]);
    }
}
