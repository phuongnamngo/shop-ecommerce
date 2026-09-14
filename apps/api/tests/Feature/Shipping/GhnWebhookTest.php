<?php

use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use App\Models\WebhookEvent;

function ghnShippedOrder(string $tracking = 'GHN-TEST-1'): Order
{
    config(['commerce.ghn.webhook_token' => 'ghn-webhook-secret']);
    $method = ShippingMethod::query()->firstOrCreate(
        ['code' => 'ghn'],
        ['name' => 'GHN', 'provider' => 'ghn', 'status' => 'active'],
    );
    $order = Order::factory()->create([
        'status' => 'shipped',
        'shipping_method_id' => $method->id,
        'ghn_service_id' => 1,
    ]);
    OrderShipment::query()->create([
        'order_id' => $order->id,
        'shipping_method_id' => $method->id,
        'tracking_number' => $tracking,
        'carrier_code' => 'ghn',
        'status' => 'shipped',
    ]);

    return $order;
}

it('updates shipment status from a GHN webhook and is idempotent for the same raw status', function () {
    $order = ghnShippedOrder();

    $this->withHeader('Token', 'ghn-webhook-secret')
        ->postJson('/api/v1/webhooks/ghn', ['OrderCode' => 'GHN-TEST-1', 'Status' => 'delivering'])
        ->assertOk()
        ->assertJsonPath('data.ok', true);

    expect($order->shipments()->first()->status)->toBe('in_transit')
        ->and($order->refresh()->status)->toBe('shipped');
    expect(WebhookEvent::query()->count())->toBe(1);

    $this->withHeader('Token', 'ghn-webhook-secret')
        ->postJson('/api/v1/webhooks/ghn', ['OrderCode' => 'GHN-TEST-1', 'Status' => 'delivering'])
        ->assertOk();

    expect(WebhookEvent::query()->count())->toBe(1)
        ->and($order->shipments()->first()->status)->toBe('in_transit');
});

it('does not complete the order when GHN reports delivered', function () {
    $order = ghnShippedOrder();

    $this->withHeader('Token', 'ghn-webhook-secret')
        ->postJson('/api/v1/webhooks/ghn', ['OrderCode' => 'GHN-TEST-1', 'Status' => 'delivered'])
        ->assertOk();

    expect($order->shipments()->first()->status)->toBe('delivered')
        ->and($order->refresh()->status)->toBe('shipped');
});

it('acknowledges unknown tracking without recording a webhook event', function () {
    config(['commerce.ghn.webhook_token' => 'ghn-webhook-secret']);

    $this->withHeader('Token', 'ghn-webhook-secret')
        ->postJson('/api/v1/webhooks/ghn', ['OrderCode' => 'UNKNOWN', 'Status' => 'delivering'])
        ->assertOk()
        ->assertJsonPath('data.ok', true);

    expect(WebhookEvent::query()->count())->toBe(0);
});

it('rejects a GHN webhook with a bad token', function () {
    config(['commerce.ghn.webhook_token' => 'ghn-webhook-secret']);

    $this->withHeader('Token', 'wrong')
        ->postJson('/api/v1/webhooks/ghn', ['OrderCode' => 'GHN-TEST-1', 'Status' => 'delivering'])
        ->assertUnauthorized();
});

it('keeps shipment status for exception payloads', function () {
    $order = ghnShippedOrder();

    $this->withHeader('Token', 'ghn-webhook-secret')
        ->postJson('/api/v1/webhooks/ghn', ['OrderCode' => 'GHN-TEST-1', 'Status' => 'exception'])
        ->assertOk();

    expect($order->shipments()->first()->status)->toBe('shipped');
    expect(WebhookEvent::query()->count())->toBe(1);
});
