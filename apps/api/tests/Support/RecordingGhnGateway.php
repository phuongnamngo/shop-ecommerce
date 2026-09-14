<?php

namespace Tests\Support;

use App\Contracts\ShippingGateway;
use App\Models\Order;
use App\Services\Shipping\FakeGhnGateway;
use App\Services\Shipping\GhnWebhookEvent;
use App\Services\Shipping\WaybillResult;

final class RecordingGhnGateway implements ShippingGateway
{
    public int $quoteCalls = 0;

    public ?int $lastWeightGrams = null;

    public function __construct(private readonly FakeGhnGateway $inner = new FakeGhnGateway) {}

    public function quote(string $toDistrictCode, string $toWardCode, int $weightGrams): array
    {
        $this->quoteCalls++;
        $this->lastWeightGrams = $weightGrams;

        return $this->inner->quote($toDistrictCode, $toWardCode, $weightGrams);
    }

    public function createWaybill(Order $order): WaybillResult
    {
        return $this->inner->createWaybill($order);
    }

    public function parseWebhook(array $payload): GhnWebhookEvent
    {
        return $this->inner->parseWebhook($payload);
    }
}
