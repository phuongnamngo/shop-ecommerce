<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('records a product patch with admin causer', function () {
    $product = Product::factory()->create(['name' => 'Old']);
    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$product->id, ['name' => 'New Name'])
        ->assertOk();

    $row = Activity::query()->where('log_name', 'product')->latest('id')->first();
    expect($row)->not->toBeNull()
        ->and($row->event)->toBe('updated')
        ->and($row->causer_id)->toBe($admin->id)
        ->and(json_encode($row->properties))->toContain('New Name');
});

it('records an order create without admin causer or guest tokens', function () {
    $order = Order::factory()->create([
        'guest_lookup_token_hash' => 'hash-secret',
        'guest_lookup_token_cipher' => 'cipher-secret',
    ]);

    $row = Activity::query()->where('log_name', 'order')->where('subject_id', $order->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->causer_id)->toBeNull()
        ->and(json_encode($row->properties))->not->toContain('guest_lookup_token')
        ->and(json_encode($row->properties))->not->toContain('hash-secret')
        ->and(json_encode($row->properties))->not->toContain('cipher-secret');
});

it('records a stock movement create with admin causer', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();
    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin');

    $movement = StockMovement::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'type' => 'receipt',
        'qty' => 5,
        'note' => 'Initial receipt',
    ]);

    $row = Activity::query()->where('log_name', 'stock_movement')->where('subject_id', $movement->id)->first();
    expect($row)->not->toBeNull()
        ->and($row->event)->toBe('created')
        ->and($row->causer_id)->toBe($admin->id);
});

it('records a product variant sku update', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'SKU-OLD']);
    $admin = catalogAdmin();
    $this->actingAs($admin, 'admin');

    $variant->update(['sku' => 'SKU-LOG']);

    $row = Activity::query()->where('log_name', 'product_variant')->where('subject_id', $variant->id)->latest('id')->first();
    expect($row)->not->toBeNull()
        ->and($row->event)->toBe('updated')
        ->and($row->causer_id)->toBe($admin->id)
        ->and(json_encode($row->properties))->toContain('SKU-LOG');
});

it('forbids guests from listing activity', function () {
    $this->getJson('/api/v1/admin/activity')->assertUnauthorized();
});

it('lists activity for staff newest first with changes', function () {
    $first = Product::factory()->create(['name' => 'First']);
    $second = Product::factory()->create(['name' => 'Second']);
    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$first->id, ['name' => 'First New'])
        ->assertOk();
    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$second->id, ['name' => 'Second New'])
        ->assertOk();

    $res = $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/activity?log_name=product')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    expect($res->json('data.0.id'))->toBeGreaterThan($res->json('data.1.id'))
        ->and($res->json('data.0.log_name'))->toBe('product')
        ->and($res->json('data.0.causer_id'))->toBe($admin->id)
        ->and($res->json('data.0.causer_name'))->toBe($admin->name)
        ->and($res->json('data.0.changes.name.new'))->toBe('Second New')
        ->and($res->json('data.0.changes.name.old'))->toBe('Second');
});

it('rejects an invalid log_name filter', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/activity?log_name=foo')
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::VALIDATION_FAILED]);
});

it('does not expose post or delete activity routes', function () {
    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/activity', [])
        ->assertStatus(405);

    $this->actingAs($admin, 'admin')
        ->deleteJson('/api/v1/admin/activity')
        ->assertStatus(405);
});
