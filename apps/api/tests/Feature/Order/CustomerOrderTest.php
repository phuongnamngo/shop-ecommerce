<?php

use App\Models\Customer;
use App\Models\Order;

it('returns only orders owned by the authenticated customer', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $other = Customer::factory()->create(['status' => 'active']);
    $own = Order::factory()->create(['customer_id' => $customer->id]);
    $foreign = Order::factory()->create(['customer_id' => $other->id]);

    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);
    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders/'.$foreign->id)->assertNotFound();
    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/orders/'.$own->id)->assertOk()->assertJsonPath('data.number', $own->number);
});
