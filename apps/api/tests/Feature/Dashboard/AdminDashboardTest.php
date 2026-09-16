<?php

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('returns empty metrics as zero for staff with orders.view', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.today.order_count', 0)
        ->assertJsonPath('data.today.revenue', '0.00')
        ->assertJsonPath('data.month.order_count', 0)
        ->assertJsonPath('data.month.revenue', '0.00')
        ->assertJsonPath('data.currency', 'VND')
        ->assertJsonCount(30, 'data.revenue_series')
        ->assertJsonPath('data.revenue_series.0.date', '2026-08-18')
        ->assertJsonPath('data.revenue_series.0.order_count', 0)
        ->assertJsonPath('data.revenue_series.0.revenue', '0.00')
        ->assertJsonPath('data.revenue_series.29.date', '2026-09-16')
        ->assertJsonPath('data.revenue_series.29.order_count', 0)
        ->assertJsonPath('data.revenue_series.29.revenue', '0.00')
        ->assertJsonPath('data.top_skus', [])
        ->assertJsonPath('data.low_stock', [])
        ->assertJsonPath('data.low_stock_count', 0);
});

it('counts paid-plus today into both today and month', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

    Order::factory()->create([
        'status' => 'paid',
        'grand_total' => 100000,
        'currency' => 'VND',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.today.order_count', 1)
        ->assertJsonPath('data.today.revenue', '100000.00')
        ->assertJsonPath('data.month.order_count', 1)
        ->assertJsonPath('data.month.revenue', '100000.00')
        ->assertJsonPath('data.revenue_series.29.order_count', 1)
        ->assertJsonPath('data.revenue_series.29.revenue', '100000.00');
});

it('excludes pending cancelled soft-deleted and outside window', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

    Order::factory()->create(['status' => 'pending', 'grand_total' => 50000, 'created_at' => now()]);
    Order::factory()->create(['status' => 'cancelled', 'grand_total' => 50000, 'created_at' => now()]);
    $deleted = Order::factory()->create(['status' => 'paid', 'grand_total' => 50000, 'created_at' => now()]);
    $deleted->delete();
    Order::factory()->create([
        'status' => 'paid',
        'grand_total' => 50000,
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subMonths(2),
    ]);
    Order::factory()->create([
        'status' => 'paid',
        'grand_total' => 75000,
        'currency' => 'USD',
        'created_at' => now(),
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.today.order_count', 0)
        ->assertJsonPath('data.today.revenue', '0.00')
        ->assertJsonPath('data.month.order_count', 0)
        ->assertJsonPath('data.month.revenue', '0.00')
        ->assertJsonCount(30, 'data.revenue_series')
        ->assertJsonPath('data.revenue_series.29.order_count', 0);
});

it('keeps calendar month separate from the rolling 30-day series', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

    Order::factory()->create([
        'status' => 'paid',
        'grand_total' => 40000,
        'currency' => 'VND',
        'created_at' => Carbon::parse('2026-08-20 08:00:00', 'UTC'),
        'updated_at' => Carbon::parse('2026-08-20 08:00:00', 'UTC'),
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.month.order_count', 0)
        ->assertJsonPath('data.month.revenue', '0.00')
        ->assertJsonPath('data.revenue_series.2.date', '2026-08-20')
        ->assertJsonPath('data.revenue_series.2.order_count', 1)
        ->assertJsonPath('data.revenue_series.2.revenue', '40000.00');
});

it('ranks top skus by qty in the rolling 30 days and ignores soft-deleted orders', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

    $a = Order::factory()->create(['status' => 'paid', 'currency' => 'VND', 'grand_total' => 300, 'created_at' => now()]);
    dashboardPaidItem($a, 'SKU-B', 'Bee', 2, 200);
    dashboardPaidItem($a, 'SKU-A', 'Aye', 5, 100);
    $tie = Order::factory()->create(['status' => 'paid', 'currency' => 'VND', 'grand_total' => 20, 'created_at' => now()]);
    dashboardPaidItem($tie, 'SKU-C', 'Cee', 2, 10);
    dashboardPaidItem($tie, 'SKU-D', 'Dee', 2, 10);
    $pending = Order::factory()->create(['status' => 'pending', 'currency' => 'VND', 'grand_total' => 999, 'created_at' => now()]);
    dashboardPaidItem($pending, 'SKU-Z', 'Nope', 99, 999);
    $gone = Order::factory()->create(['status' => 'paid', 'currency' => 'VND', 'grand_total' => 50, 'created_at' => now()]);
    dashboardPaidItem($gone, 'SKU-G', 'Gone', 50, 50);
    $gone->delete();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.top_skus.0.sku', 'SKU-A')
        ->assertJsonPath('data.top_skus.0.qty', 5)
        ->assertJsonPath('data.top_skus.0.name', 'Aye')
        ->assertJsonPath('data.top_skus.1.sku', 'SKU-B')
        ->assertJsonPath('data.top_skus.1.qty', 2)
        ->assertJsonPath('data.top_skus.2.sku', 'SKU-C')
        ->assertJsonPath('data.top_skus.3.sku', 'SKU-D')
        ->assertJsonCount(4, 'data.top_skus');
});

it('lists low-stock rows under threshold without aggregating warehouses', function () {
    $v = ProductVariant::factory()->create();
    $w1 = Warehouse::factory()->create();
    $w2 = Warehouse::factory()->create();
    StockItem::query()->create(['warehouse_id' => $w1->id, 'product_variant_id' => $v->id, 'qty_on_hand' => 4, 'qty_reserved' => 1]);
    StockItem::query()->create(['warehouse_id' => $w2->id, 'product_variant_id' => $v->id, 'qty_on_hand' => 2, 'qty_reserved' => 0]);
    $vZero = ProductVariant::factory()->create();
    $vHigh = ProductVariant::factory()->create();
    StockItem::query()->create(['warehouse_id' => $w1->id, 'product_variant_id' => $vZero->id, 'qty_on_hand' => 0, 'qty_reserved' => 0]);
    StockItem::query()->create(['warehouse_id' => $w1->id, 'product_variant_id' => $vHigh->id, 'qty_on_hand' => 9, 'qty_reserved' => 0]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.low_stock_count', 2)
        ->assertJsonCount(2, 'data.low_stock')
        ->assertJsonPath('data.low_stock.0.available_qty', 2)
        ->assertJsonPath('data.low_stock.1.available_qty', 3)
        ->assertJsonPath('data.low_stock.0.sku', $v->sku)
        ->assertJsonPath('data.low_stock.0.warehouse_code', $w2->code);
});

it('caps the low-stock list at ten but not the count', function () {
    $warehouse = Warehouse::factory()->create();
    foreach (range(1, 11) as $i) {
        $variant = ProductVariant::factory()->create();
        StockItem::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'qty_on_hand' => 1,
            'qty_reserved' => 0,
        ]);
    }

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.low_stock_count', 11)
        ->assertJsonCount(10, 'data.low_stock');
});

it('forbids staff without orders.view', function () {
    $admin = catalogAdmin('staff');
    Permission::findByName('orders.view', 'admin')->removeRole('staff');
    $admin->forgetCachedPermissions();

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertForbidden();
});
