<?php

use App\Models\Order;
use App\Services\Shipping\FakeGhnGateway;
use App\Support\ShippingWeight;

it('fake gateway returns two services or none when down', function () {
    $up = new FakeGhnGateway;
    $rows = $up->quote('1484', '1A0106', 500);
    expect($rows)->toHaveCount(2)
        ->and($rows[0]->serviceId)->toBe(1)
        ->and($rows[0]->fee)->toBe(25000)
        ->and($rows[1]->serviceId)->toBe(2)
        ->and($rows[1]->fee)->toBe(32000);

    expect((new FakeGhnGateway(false))->quote('1484', '1A0106', 500))->toBe([]);
});

it('fake waybill uses GHN-TEST order number', function () {
    $order = Order::factory()->create(['number' => 'ORD-ABC']);
    $result = (new FakeGhnGateway)->createWaybill($order);
    expect($result->ok)->toBeTrue()
        ->and($result->trackingNumber)->toBe('GHN-TEST-ORD-ABC');

    $down = (new FakeGhnGateway(false))->createWaybill($order);
    expect($down->ok)->toBeFalse()->and($down->trackingNumber)->toBeNull();
});

it('treats missing or non-positive weight as the commerce default', function () {
    config(['commerce.default_weight_grams' => 500]);
    expect(ShippingWeight::grams(null))->toBe(500)
        ->and(ShippingWeight::grams(0))->toBe(500)
        ->and(ShippingWeight::grams(-1))->toBe(500)
        ->and(ShippingWeight::grams(750))->toBe(750);
});
