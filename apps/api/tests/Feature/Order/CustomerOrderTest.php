<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;

it('returns only orders owned by the authenticated customer', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $other = Customer::factory()->create(['status' => 'active']);
    $own = Order::factory()->create(['customer_id' => $customer->id]);
    $foreign = Order::factory()->create(['customer_id' => $other->id]);
    OrderStatusHistory::query()->create([
        'order_id' => $own->id,
        'from_status' => null,
        'to_status' => 'pending',
        'changed_by_admin_id' => null,
        'note' => 'internal ops note',
    ]);

    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);
    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders/'.$foreign->id)->assertNotFound();
    $detail = $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders/'.$own->id)->assertOk()->assertJsonPath('data.number', $own->number)->json('data.status_history.0');
    expect($detail)->toHaveKeys(['from_status', 'to_status', 'created_at'])
        ->and($detail)->not->toHaveKeys(['changed_by_admin_id', 'note']);
});

it('exposes product identifiers on order items without flash sale internals', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create(['slug' => 'order-item-watch']);
    $variant = $product->variants()->firstOrFail();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'flash_sale_item_id' => null,
        'sku' => $variant->sku,
        'name' => 'Item',
        'qty' => 1,
        'unit_price' => $variant->price,
        'line_total' => $variant->price,
    ]);
    $variant->delete();

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/orders/'.$order->id)
        ->assertOk()
        ->assertJsonPath('data.items.0.product_variant_id', $variant->id)
        ->assertJsonPath('data.items.0.product_id', $product->id)
        ->assertJsonPath('data.items.0.product_slug', 'order-item-watch')
        ->assertJsonPath('data.items.0.sku', $variant->sku)
        ->assertJsonMissingPath('data.items.0.flash_sale_item_id')
        ->assertJsonMissingPath('data.items.0.order_id');
});
