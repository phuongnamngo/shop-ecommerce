<?php

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('guards admin order routes with order permissions and filters status', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('staff');
    Order::factory()->create(['status' => 'pending']);
    Order::factory()->create(['status' => 'completed']);
    $this->actingAs($admin, 'admin')->getJson('/api/v1/admin/orders?per_page=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2);
    $this->actingAs($admin, 'admin')->getJson('/api/v1/admin/orders?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'pending')
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    Permission::findByName('orders.view', 'admin')->removeRole('staff');
    $admin->forgetCachedPermissions();
    $this->actingAs($admin, 'admin')->getJson('/api/v1/admin/orders')->assertForbidden();
});

it('records allowed transitions and releases cancellation reservations once', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $stock = StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 5, 'qty_reserved' => 2]);
    $order = Order::factory()->create(['status' => 'pending']);
    StockReservation::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'order_id' => $order->id, 'qty' => 2, 'status' => 'active']);

    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid', 'note' => 'manual'])->assertOk()->assertJsonPath('data.status', 'paid');
    $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'from_status' => 'pending', 'to_status' => 'paid', 'changed_by_admin_id' => $admin->id]);
    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'cancelled'])->assertOk();
    expect($stock->refresh()->qty_reserved)->toBe(0);
    $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'released']);
    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'cancelled'])->assertConflict()->assertJsonPath('errors.0.code', 'ORDER_INVALID_TRANSITION');
    expect($stock->refresh()->qty_reserved)->toBe(0);
});

it('rejects shipped via PATCH status with validation error', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $order = Order::factory()->create(['status' => 'fulfilling']);
    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'shipped'])
        ->assertUnprocessable();
});

it('settles a pending payment transaction when admin marks paid', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $method = PaymentMethod::query()->create(['code' => 'cod', 'name' => 'COD', 'is_active' => true]);
    $order = Order::factory()->create(['status' => 'pending', 'grand_total' => 100000]);
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'cod',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => $order->grand_total,
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])->assertOk();
    expect($txn->refresh()->status)->toBe('succeeded');
});

it('rejects forbidden state transitions', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $order = Order::factory()->create(['status' => 'pending']);
    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'completed'])->assertConflict()->assertJsonPath('errors.0.code', 'ORDER_INVALID_TRANSITION');
});

it('accepts each documented order state transition', function (string $from, string $to) {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $order = Order::factory()->create(['status' => $from]);
    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => $to])->assertOk()->assertJsonPath('data.status', $to);
})->with([
    ['pending', 'paid'], ['pending', 'cancelled'], ['paid', 'fulfilling'], ['paid', 'cancelled'],
    ['fulfilling', 'cancelled'], ['shipped', 'completed'],
]);

it('admin order show includes shipments array and mapped status history', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $order = Order::factory()->create(['status' => 'pending']);
    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid', 'note' => 'cod'])
        ->assertOk();
    $res = $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/orders/'.$order->id)
        ->assertOk()
        ->assertJsonPath('data.status', 'paid')
        ->assertJsonStructure([
            'data' => [
                'shipments',
                'status_history' => [['from_status', 'to_status', 'created_at']],
            ],
        ]);
    expect($res->json('data.shipments'))->toBeArray();
    expect($res->json('data.status_history.0'))->not->toHaveKeys(['order_id', 'changed_by_admin_id']);
});
