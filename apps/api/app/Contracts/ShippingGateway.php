<?php

namespace App\Contracts;

use App\Models\Order;
use App\Services\Shipping\GhnQuoteRow;
use App\Services\Shipping\GhnWebhookEvent;
use App\Services\Shipping\WaybillResult;

interface ShippingGateway
{
    /**
     * @return list<GhnQuoteRow>
     */
    public function quote(string $toDistrictCode, string $toWardCode, int $weightGrams): array;

    public function createWaybill(Order $order): WaybillResult;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhook(array $payload): GhnWebhookEvent;
}
