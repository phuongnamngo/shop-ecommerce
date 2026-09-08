<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;

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
