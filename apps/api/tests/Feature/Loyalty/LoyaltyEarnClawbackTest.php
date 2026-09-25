<?php

use App\Contracts\PaymentGateway;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyLedgerEntry;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Order\OrderService;
use App\Services\Payment\FakePaymentGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
});

function loyaltyAdmin(): AdminUser
{
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');

    return $admin;
}

function markOrderPaid(Order $order): void
{
    test()->actingAs(loyaltyAdmin(), 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])
        ->assertOk();
}

it('credits two points when a customer order is marked paid', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
        'grand_total' => 2500,
    ]);

    markOrderPaid($order);

    $account = LoyaltyAccount::query()->where('customer_id', $customer->id)->first();
    expect($account)->not->toBeNull()
        ->and($account->points_balance)->toBe(2);
    $this->assertDatabaseHas('loyalty_ledger_entries', [
        'loyalty_account_id' => $account->id,
        'delta' => 2,
        'reason' => 'order.paid',
        'reference_type' => Order::class,
        'reference_id' => $order->id,
        'balance_after' => 2,
    ]);

    test()->actingAs(loyaltyAdmin(), 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'ORDER_INVALID_TRANSITION');
    expect(LoyaltyLedgerEntry::query()->where('reason', 'order.paid')->count())->toBe(1)
        ->and($account->refresh()->points_balance)->toBe(2);
});

it('credits points from markPaidBySystem and ignores a second credit', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
        'grand_total' => 2500,
    ]);

    app(OrderService::class)->markPaidBySystem($order, 'ipn');

    expect(LoyaltyAccount::query()->where('customer_id', $customer->id)->value('points_balance'))->toBe(2);

    app(LoyaltyService::class)->creditForPaidOrder($order->refresh());

    expect(LoyaltyLedgerEntry::query()->where('reason', 'order.paid')->count())->toBe(1)
        ->and(LoyaltyAccount::query()->where('customer_id', $customer->id)->value('points_balance'))->toBe(2);
});

it('credits one point for a fractional total above 1000', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
        'grand_total' => 1500.50,
    ]);

    markOrderPaid($order);

    expect(LoyaltyAccount::query()->where('customer_id', $customer->id)->value('points_balance'))->toBe(1);
});

it('does not write a ledger when the total is under 1000', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
        'grand_total' => 999,
    ]);

    markOrderPaid($order);

    expect(LoyaltyAccount::query()->count())->toBe(0)
        ->and(LoyaltyLedgerEntry::query()->count())->toBe(0);
});

it('does not create a loyalty account for a guest order', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'status' => 'pending',
        'grand_total' => 2500,
    ]);

    markOrderPaid($order);

    expect(LoyaltyAccount::query()->count())->toBe(0)
        ->and(LoyaltyLedgerEntry::query()->count())->toBe(0);
});

it('does not touch loyalty when a pending order is cancelled', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
        'grand_total' => 2500,
    ]);

    test()->actingAs(loyaltyAdmin(), 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'cancelled'])
        ->assertOk();

    expect(LoyaltyAccount::query()->count())->toBe(0)
        ->and(LoyaltyLedgerEntry::query()->count())->toBe(0);
});

it('reverses the earned points once when a refund cancels the order', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'pending',
        'grand_total' => 2500,
    ]);
    markOrderPaid($order);
    $method = PaymentMethod::query()->firstOrCreate(
        ['code' => 'cod'],
        ['name' => 'COD', 'is_active' => true],
    );
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'cod',
        'provider_txn_id' => null,
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 2500,
        'status' => 'succeeded',
    ]);
    $admin = loyaltyAdmin();
    $refundId = test()->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'cancel'])
        ->assertCreated()
        ->json('data.id');

    test()->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertOk();

    expect($order->refresh()->status)->toBe('cancelled')
        ->and(LoyaltyAccount::query()->where('customer_id', $customer->id)->value('points_balance'))->toBe(0);
    $this->assertDatabaseHas('loyalty_ledger_entries', [
        'reason' => 'order.cancelled',
        'reference_type' => Order::class,
        'reference_id' => $order->id,
        'delta' => -2,
        'balance_after' => 0,
    ]);
    expect(LoyaltyLedgerEntry::query()->where('reason', 'order.cancelled')->count())->toBe(1);
});

it('does not reverse points when the balance cannot cover the earned delta', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'paid',
        'grand_total' => 2500,
    ]);
    $account = LoyaltyAccount::query()->create([
        'customer_id' => $customer->id,
        'points_balance' => 0,
    ]);
    LoyaltyLedgerEntry::query()->create([
        'loyalty_account_id' => $account->id,
        'delta' => 2,
        'reason' => 'order.paid',
        'reference_type' => Order::class,
        'reference_id' => $order->id,
        'balance_after' => 2,
    ]);

    app(LoyaltyService::class)->reverseForCancelledOrder($order);

    expect(LoyaltyLedgerEntry::query()->where('reason', 'order.cancelled')->count())->toBe(0)
        ->and($account->refresh()->points_balance)->toBe(0);
});
