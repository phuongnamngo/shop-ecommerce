<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingGateway;
use App\Models\Order;

final class FakeGhnGateway implements ShippingGateway
{
    public function __construct(private readonly bool $available = true) {}

    public function quote(string $toDistrictCode, string $toWardCode, int $weightGrams): array
    {
        if (! $this->available) {
            return [];
        }

        return [
            new GhnQuoteRow(1, 'GHN Chuẩn', 25000),
            new GhnQuoteRow(2, 'GHN Nhanh', 32000),
        ];
    }

    public function createWaybill(Order $order): WaybillResult
    {
        if (! $this->available) {
            return new WaybillResult(ok: false, trackingNumber: null, payload: []);
        }

        return new WaybillResult(
            ok: true,
            trackingNumber: 'GHN-TEST-'.$order->number,
            payload: [],
        );
    }

    public function parseWebhook(array $payload): GhnWebhookEvent
    {
        return new GhnWebhookEvent(
            orderCode: (string) ($payload['OrderCode'] ?? $payload['order_code'] ?? ''),
            rawStatus: (string) ($payload['Status'] ?? $payload['status'] ?? ''),
            tracking: isset($payload['OrderCode']) ? (string) $payload['OrderCode'] : null,
        );
    }
}
