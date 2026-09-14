<?php

use App\Contracts\ShippingGateway;
use App\Models\Order;
use App\Services\Shipping\GhnGateway;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'commerce.ghn.token' => 'ghn-token',
        'commerce.ghn.shop_id' => '123',
        'commerce.ghn.from_district_id' => '1442',
        'commerce.ghn.from_ward_code' => '21211',
        'commerce.ghn.from_phone' => '0900000000',
        'commerce.ghn.from_address' => '1 Pickup',
        'commerce.ghn.base_url' => 'https://dev-online-gateway.ghn.vn',
        'commerce.ghn.default_lwh_cm' => [10, 10, 10],
        'commerce.default_weight_grams' => 500,
    ]);
    $this->app->instance(ShippingGateway::class, $this->app->make(GhnGateway::class));
});

it('quotes available GHN services with calculated fees', function () {
    Http::fake([
        'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/available-services' => Http::response([
            'code' => 200,
            'data' => [
                ['service_id' => 53320, 'short_name' => 'GHN Nhanh', 'service_type_id' => 2],
            ],
        ]),
        'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee' => Http::response([
            'code' => 200,
            'data' => ['total' => 27500],
        ]),
    ]);

    $rows = $this->app->make(ShippingGateway::class)->quote('1484', '1A0106', 500);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->serviceId)->toBe(53320)
        ->and($rows[0]->name)->toBe('GHN Nhanh')
        ->and($rows[0]->fee)->toBe(27500);

    Http::assertSentCount(2);
});

it('returns an empty quote list when the token is missing', function () {
    config(['commerce.ghn.token' => '']);
    $this->app->instance(ShippingGateway::class, $this->app->make(GhnGateway::class));
    Http::fake();

    expect($this->app->make(ShippingGateway::class)->quote('1484', '1A0106', 500))->toBe([]);
    Http::assertNothingSent();
});

it('creates a waybill and returns the GHN order code', function () {
    Http::fake([
        'https://dev-online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/create' => Http::response([
            'code' => 200,
            'data' => ['order_code' => 'LMP123'],
        ]),
    ]);

    $order = Order::factory()->create([
        'number' => 'ORD-WAY',
        'ghn_service_id' => 53320,
        'grand_total' => 100000,
        'shipping_address_snapshot' => [
            'recipient_name' => 'A',
            'phone' => '0901111111',
            'address_line' => '1 Road',
            'ward_code' => '1A0106',
            'district_code' => '1484',
            'province_code' => '201',
        ],
    ]);

    $result = $this->app->make(ShippingGateway::class)->createWaybill($order);

    expect($result->ok)->toBeTrue()->and($result->trackingNumber)->toBe('LMP123');
    Http::assertSentCount(1);
});

it('returns a failed waybill when the token is missing', function () {
    config(['commerce.ghn.token' => '']);
    $this->app->instance(ShippingGateway::class, $this->app->make(GhnGateway::class));
    $order = Order::factory()->create(['ghn_service_id' => 1]);

    $result = $this->app->make(ShippingGateway::class)->createWaybill($order);

    expect($result->ok)->toBeFalse()->and($result->trackingNumber)->toBeNull();
});

it('parses a GHN webhook payload', function () {
    $event = $this->app->make(ShippingGateway::class)->parseWebhook([
        'OrderCode' => 'LMP123',
        'Status' => 'delivering',
    ]);

    expect($event->orderCode)->toBe('LMP123')
        ->and($event->rawStatus)->toBe('delivering')
        ->and($event->tracking)->toBe('LMP123');
});
