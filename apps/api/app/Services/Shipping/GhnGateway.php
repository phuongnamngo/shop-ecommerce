<?php

namespace App\Services\Shipping;

use App\Contracts\ShippingGateway;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Support\ShippingWeight;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class GhnGateway implements ShippingGateway
{
    public function quote(string $toDistrictCode, string $toWardCode, int $weightGrams): array
    {
        if ($this->token() === '') {
            return [];
        }

        $services = $this->post('/shiip/public-api/v2/shipping-order/available-services', [
            'shop_id' => (int) config('commerce.ghn.shop_id'),
            'from_district' => (int) config('commerce.ghn.from_district_id'),
            'to_district' => (int) $toDistrictCode,
        ]);
        if (! $services->ok()) {
            return [];
        }

        $rows = [];
        foreach ($services->json('data') ?? [] as $service) {
            $serviceId = (int) ($service['service_id'] ?? 0);
            if ($serviceId === 0) {
                continue;
            }
            $fee = $this->post('/shiip/public-api/v2/shipping-order/fee', [
                'from_district_id' => (int) config('commerce.ghn.from_district_id'),
                'from_ward_code' => (string) config('commerce.ghn.from_ward_code'),
                'service_id' => $serviceId,
                'to_district_id' => (int) $toDistrictCode,
                'to_ward_code' => $toWardCode,
                'weight' => $weightGrams,
                'insurance_value' => 0,
            ]);
            if (! $fee->ok()) {
                continue;
            }
            $total = (int) ($fee->json('data.total') ?? 0);
            $rows[] = new GhnQuoteRow(
                $serviceId,
                (string) ($service['short_name'] ?? 'GHN'),
                $total,
            );
        }

        return $rows;
    }

    public function createWaybill(Order $order): WaybillResult
    {
        if ($this->token() === '') {
            return new WaybillResult(ok: false, trackingNumber: null, payload: ['error' => 'missing_token']);
        }

        $address = $order->shipping_address_snapshot ?? [];
        $weight = 0;
        foreach ($order->items as $item) {
            $grams = ShippingWeight::grams($item->variant?->weight_grams);
            $weight += $grams * (int) $item->qty;
        }
        if ($weight <= 0) {
            $weight = ShippingWeight::grams(null);
        }

        $lwh = array_values((array) config('commerce.ghn.default_lwh_cm', [10, 10, 10]));
        $cod = 0;
        $txn = PaymentTransaction::query()->where('order_id', $order->id)->latest('id')->first();
        if ($txn !== null && $txn->provider === 'cod') {
            $cod = (int) $order->grand_total;
        }

        $response = $this->post('/shiip/public-api/v2/shipping-order/create', [
            'payment_type_id' => 1,
            'required_note' => 'KHONGCHOXEMHANG',
            'from_name' => 'Shop',
            'from_phone' => (string) config('commerce.ghn.from_phone'),
            'from_address' => (string) config('commerce.ghn.from_address'),
            'from_ward_code' => (string) config('commerce.ghn.from_ward_code'),
            'from_district_id' => (int) config('commerce.ghn.from_district_id'),
            'to_name' => (string) ($address['recipient_name'] ?? ''),
            'to_phone' => (string) ($address['phone'] ?? ''),
            'to_address' => (string) ($address['address_line'] ?? ''),
            'to_ward_code' => (string) ($address['ward_code'] ?? ''),
            'to_district_id' => (int) ($address['district_code'] ?? 0),
            'cod_amount' => $cod,
            'weight' => $weight,
            'length' => (int) ($lwh[0] ?? 10),
            'width' => (int) ($lwh[1] ?? 10),
            'height' => (int) ($lwh[2] ?? 10),
            'service_id' => (int) $order->ghn_service_id,
            'client_order_code' => (string) $order->number,
        ]);

        $payload = $response->json() ?? [];
        $code = (string) ($response->json('data.order_code') ?? '');
        if (! $response->ok() || $code === '') {
            return new WaybillResult(ok: false, trackingNumber: null, payload: is_array($payload) ? $payload : []);
        }

        return new WaybillResult(ok: true, trackingNumber: $code, payload: is_array($payload) ? $payload : []);
    }

    public function parseWebhook(array $payload): GhnWebhookEvent
    {
        $orderCode = (string) ($payload['OrderCode'] ?? $payload['order_code'] ?? '');

        return new GhnWebhookEvent(
            orderCode: $orderCode,
            rawStatus: (string) ($payload['Status'] ?? $payload['status'] ?? ''),
            tracking: $orderCode !== '' ? $orderCode : null,
        );
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(string $path, array $body): Response
    {
        return Http::asJson()
            ->baseUrl(rtrim((string) config('commerce.ghn.base_url'), '/'))
            ->withHeaders([
                'Token' => $this->token(),
                'ShopId' => (string) config('commerce.ghn.shop_id'),
            ])
            ->post($path, $body);
    }

    private function token(): string
    {
        return (string) config('commerce.ghn.token');
    }
}
