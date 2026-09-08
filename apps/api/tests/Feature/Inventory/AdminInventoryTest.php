<?php

use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('lists warehouses and filters stock items for an authorized admin', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();
    StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 12,
        'qty_reserved' => 2,
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/inventory/warehouses')
        ->assertOk()
        ->assertJsonPath('data.0.id', $warehouse->id)
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/inventory/stock-items?warehouse_id='.$warehouse->id.'&product_variant_id='.$variant->id)
        ->assertOk()
        ->assertJsonPath('data.0.qty_on_hand', 12)
        ->assertJsonPath('data.0.qty_reserved', 2)
        ->assertJsonPath('data.0.available_qty', 10)
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});

it('paginates inventory lists and clamps per page', function () {
    Warehouse::factory()->count(2)->create();

    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/inventory/warehouses?per_page=1&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2);

    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/inventory/warehouses?per_page=999')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('forbids an admin without inventory view permission', function () {
    Role::findByName('staff', 'admin')->revokePermissionTo('inventory.view');
    $admin = catalogAdmin('staff');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/inventory/warehouses')
        ->assertForbidden();
});

it('records a receipt and increases stock on hand', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();
    $stock = StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/inventory/movements', [
            'warehouse_id' => $warehouse->id,
            'product_variant_id' => $variant->id,
            'type' => 'receipt',
            'qty' => 5,
            'note' => 'Initial receipt',
        ])
        ->assertCreated()
        ->assertJsonPath('data.qty_on_hand', 5);

    expect($stock->refresh()->qty_on_hand)->toBe(5)
        ->and(StockMovement::query()->where('warehouse_id', $warehouse->id)->where('product_variant_id', $variant->id)->value('qty'))->toBe(5);
});

it('rejects an issue larger than available stock', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 4, 'qty_reserved' => 2]);
    $this->actingAs(catalogAdmin(), 'admin')->postJson('/api/v1/admin/inventory/movements', ['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'type' => 'issue', 'qty' => 3])
        ->assertUnprocessable()->assertJsonFragment(['code' => 'INVENTORY_INSUFFICIENT_STOCK']);
});

it('records a signed adjustment without dropping below reserved stock', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();
    $stock = StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 5, 'qty_reserved' => 2]);
    $this->actingAs(catalogAdmin(), 'admin')->postJson('/api/v1/admin/inventory/movements', ['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'type' => 'adjustment', 'qty' => -2])
        ->assertCreated()->assertJsonPath('data.qty_on_hand', 3);
    expect($stock->refresh()->qty_on_hand)->toBe(3);
});
