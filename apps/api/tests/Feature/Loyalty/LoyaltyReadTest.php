<?php

use App\Models\Customer;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyLedgerEntry;
use App\Models\Order;

it('returns 401 when the customer is not signed in', function () {
    $this->getJson('/api/v1/customer/loyalty')->assertUnauthorized();
});

it('returns zero when the signed-in customer has no loyalty account', function () {
    $customer = Customer::factory()->create(['status' => 'active']);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/loyalty')
        ->assertOk()
        ->assertJsonPath('data.points_balance', 0)
        ->assertJsonPath('data.entries', []);
});

it('returns the signed-in customer balance and latest entries', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $other = Customer::factory()->create(['status' => 'active']);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'paid',
        'grand_total' => 2500,
    ]);
    $account = LoyaltyAccount::query()->create([
        'customer_id' => $customer->id,
        'points_balance' => 2,
    ]);
    LoyaltyLedgerEntry::query()->create([
        'loyalty_account_id' => $account->id,
        'delta' => 2,
        'reason' => 'order.paid',
        'reference_type' => Order::class,
        'reference_id' => $order->id,
        'balance_after' => 2,
    ]);
    LoyaltyAccount::query()->create([
        'customer_id' => $other->id,
        'points_balance' => 9,
    ]);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/loyalty')
        ->assertOk()
        ->assertJsonPath('data.points_balance', 2)
        ->assertJsonPath('data.entries.0.delta', 2)
        ->assertJsonPath('data.entries.0.reason', 'order.paid')
        ->assertJsonPath('data.entries.0.order_id', $order->id)
        ->assertJsonPath('data.entries.0.balance_after', 2)
        ->assertJsonCount(1, 'data.entries')
        ->assertJsonStructure([
            'data' => [
                'points_balance',
                'entries' => [['delta', 'reason', 'order_id', 'balance_after', 'created_at']],
            ],
            'meta',
        ]);
});

it('returns at most 20 ledger entries newest first', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $account = LoyaltyAccount::query()->create([
        'customer_id' => $customer->id,
        'points_balance' => 21,
    ]);
    foreach (range(1, 21) as $delta) {
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'paid',
            'grand_total' => 1000,
        ]);
        LoyaltyLedgerEntry::query()->create([
            'loyalty_account_id' => $account->id,
            'delta' => $delta,
            'reason' => 'order.paid',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'balance_after' => $delta,
        ]);
    }

    $response = $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/loyalty')
        ->assertOk()
        ->assertJsonCount(20, 'data.entries')
        ->assertJsonPath('data.points_balance', 21);

    expect($response->json('data.entries.0.delta'))->toBe(21)
        ->and($response->json('data.entries.19.delta'))->toBe(2);
});
