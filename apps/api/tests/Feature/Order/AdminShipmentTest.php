<?php

use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('ships a fulfilling order, consumes stock, and forbids PATCH shipped', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $stock = StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 3,
    ]);
    $order = Order::factory()->create(['status' => 'fulfilling']);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'name' => 'Item',
        'qty' => 3,
        'unit_price' => $variant->price,
        'line_total' => (int) $variant->price * 3,
    ]);
    StockReservation::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'qty' => 3,
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$order->id.'/shipments', [
        'tracking_number' => 'TRACK-1',
    ])->assertCreated()->assertJsonPath('data.tracking_number', 'TRACK-1');

    expect($order->refresh()->status)->toBe('shipped')
        ->and($stock->refresh()->qty_on_hand)->toBe(7)
        ->and($stock->qty_reserved)->toBe(0);
    $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'consumed']);
    expect(StockMovement::query()->where('product_variant_id', $variant->id)->where('type', 'issue')->value('qty'))->toBe(-3);

    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$order->id.'/shipments', [
        'tracking_number' => 'TRACK-2',
    ])->assertConflict();

    $this->actingAs($admin, 'admin')->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'shipped'])
        ->assertUnprocessable();
});

it('rejects shipment without tracking or from paid status', function () {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $paid = Order::factory()->create(['status' => 'paid']);
    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$paid->id.'/shipments', [
        'tracking_number' => 'T',
    ])->assertConflict();

    $fulfilling = Order::factory()->create(['status' => 'fulfilling']);
    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$fulfilling->id.'/shipments', [])
        ->assertUnprocessable();
});

it('does not release consumed reservations via expire command', function () {
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $stock = StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 4,
        'qty_reserved' => 0,
    ]);
    $order = Order::factory()->create(['status' => 'shipped']);
    StockReservation::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'qty' => 2,
        'status' => 'consumed',
        'expires_at' => now()->subHour(),
    ]);

    Artisan::call('commerce:expire-pending-orders');

    expect($order->refresh()->status)->toBe('shipped')
        ->and($stock->refresh()->qty_on_hand)->toBe(4)
        ->and($stock->qty_reserved)->toBe(0);
    $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'consumed']);
});
