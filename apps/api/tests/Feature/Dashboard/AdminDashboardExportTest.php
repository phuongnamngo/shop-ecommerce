<?php

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\Warehouse;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('downloads an xlsx matching the metrics snapshot', function () {
    $this->travelTo(Carbon::parse('2026-09-16 12:00:00', 'UTC'));

    $order = Order::factory()->create([
        'status' => 'paid',
        'currency' => 'VND',
        'grand_total' => 100000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    dashboardPaidItem($order, 'SKU-A', 'Aye', 3, 100000);

    $variant = ProductVariant::factory()->create();
    $warehouse = Warehouse::factory()->create(['code' => 'WH-A', 'name' => 'Alpha']);
    StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 4,
        'qty_reserved' => 1,
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin');
    $metrics = $this->getJson('/api/v1/admin/dashboard/metrics')->assertOk()->json('data');

    $response = $this->get('/api/v1/admin/dashboard/export');
    $response->assertOk();
    expect($response->headers->get('content-type'))
        ->toStartWith('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('content-disposition'))
        ->toContain('dashboard-2026-09-16.xlsx');

    $tmp = sys_get_temp_dir().'/dash-'.uniqid().'.xlsx';
    file_put_contents($tmp, $response->streamedContent());
    $book = IOFactory::load($tmp);
    @unlink($tmp);

    expect($book->getSheetNames())->toBe(['Daily', 'Top SKU', 'Low stock']);

    $daily = $book->getSheetByName('Daily');
    expect($daily->getCell('A1')->getValue())->toBe('Date')
        ->and($daily->getCell('B1')->getValue())->toBe('Order count')
        ->and($daily->getCell('C1')->getValue())->toBe('Revenue')
        ->and($daily->getCell('A2')->getValue())->toBe($metrics['revenue_series'][0]['date'])
        ->and((int) $daily->getCell('B31')->getValue())->toBe($metrics['revenue_series'][29]['order_count'])
        ->and((string) $daily->getCell('C31')->getValue())->toBe($metrics['revenue_series'][29]['revenue']);

    $top = $book->getSheetByName('Top SKU');
    expect($top->getCell('A1')->getValue())->toBe('SKU')
        ->and($top->getCell('A2')->getValue())->toBe($metrics['top_skus'][0]['sku'])
        ->and((int) $top->getCell('C2')->getValue())->toBe($metrics['top_skus'][0]['qty']);

    $low = $book->getSheetByName('Low stock');
    expect($low->getCell('A1')->getValue())->toBe('SKU')
        ->and($low->getCell('A2')->getValue())->toBe($metrics['low_stock'][0]['sku'])
        ->and((int) $low->getCell('E2')->getValue())->toBe($metrics['low_stock'][0]['available_qty']);
});

it('forbids export without orders.view', function () {
    $admin = catalogAdmin('staff');
    Permission::findByName('orders.view', 'admin')->removeRole('staff');
    $admin->forgetCachedPermissions();

    $this->actingAs($admin, 'admin')
        ->get('/api/v1/admin/dashboard/export')
        ->assertForbidden();
});
