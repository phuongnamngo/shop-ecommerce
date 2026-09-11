<?php

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function adminFlashVariant(int $listPrice = 100000)
{
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => $listPrice]);

    return $variant->refresh();
}

it('allows staff to list flash sales', function () {
    FlashSale::factory()->create(['name' => 'Staff Visible']);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/flash-sales')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});

it('forbids staff from creating a flash sale', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/promotions/flash-sales', [
            'name' => 'Nope',
            'starts_at' => now()->toIso8601String(),
            'ends_at' => now()->addHour()->toIso8601String(),
        ])
        ->assertForbidden();
});

it('creates a flash sale with items and generates a ulid code', function () {
    $variant = adminFlashVariant(120000);

    $response = $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/flash-sales', [
            'name' => 'Noon drop',
            'starts_at' => now()->subHour()->toIso8601String(),
            'ends_at' => now()->addHours(3)->toIso8601String(),
            'status' => 'active',
            'items' => [
                ['product_variant_id' => $variant->id, 'sale_price' => 79000, 'qty_cap' => 20],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Noon drop')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.items.0.sale_price', '79000.00')
        ->assertJsonPath('data.items.0.qty_cap', 20)
        ->assertJsonPath('data.items.0.qty_sold', 0)
        ->assertJsonPath('data.items.0.qty_remaining', 20)
        ->assertJsonStructure(['data' => ['id', 'code', 'name', 'starts_at', 'ends_at', 'status', 'items', 'created_at', 'updated_at']]);

    expect(Str::isUlid($response->json('data.code')))->toBeTrue();
});

it('rejects ends_at before starts_at', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/flash-sales', [
            'name' => 'Bad window',
            'starts_at' => now()->addHour()->toIso8601String(),
            'ends_at' => now()->toIso8601String(),
        ])
        ->assertUnprocessable();
});

it('rejects overlapping flash sales for the same variant', function () {
    $variant = adminFlashVariant();
    $sale = FlashSale::factory()->create([
        'status' => FlashSale::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHours(4),
    ]);
    FlashSaleItem::factory()->create([
        'flash_sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 60000,
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/flash-sales', [
            'name' => 'Clash',
            'starts_at' => now()->addHour()->toIso8601String(),
            'ends_at' => now()->addHours(6)->toIso8601String(),
            'status' => 'scheduled',
            'items' => [
                ['product_variant_id' => $variant->id, 'sale_price' => 55000],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::FLASH_SALE_OVERLAP]);
});

it('returns FLASH_SALE_NOT_FOUND for a missing sale', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/promotions/flash-sales/999999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::FLASH_SALE_NOT_FOUND]);
});

it('updates items as a replace-set and soft deletes the sale', function () {
    $first = adminFlashVariant(100000);
    $second = adminFlashVariant(150000);
    $created = $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/flash-sales', [
            'name' => 'Swap',
            'starts_at' => now()->toIso8601String(),
            'ends_at' => now()->addDay()->toIso8601String(),
            'status' => 'scheduled',
            'items' => [
                ['product_variant_id' => $first->id, 'sale_price' => 80000],
            ],
        ])
        ->assertCreated();

    $id = $created->json('data.id');

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/promotions/flash-sales/'.$id, [
            'name' => 'Swapped',
            'items' => [
                ['product_variant_id' => $second->id, 'sale_price' => 99000, 'qty_cap' => 3],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Swapped')
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.product_variant_id', $second->id)
        ->assertJsonPath('data.items.0.qty_cap', 3);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/promotions/flash-sales/'.$id)
        ->assertOk();

    $this->assertSoftDeleted('flash_sales', ['id' => $id]);
});

it('filters flash sales by status and q', function () {
    FlashSale::factory()->create(['name' => 'Alpha Drop', 'status' => FlashSale::STATUS_ACTIVE]);
    FlashSale::factory()->create(['name' => 'Beta Drop', 'status' => FlashSale::STATUS_SCHEDULED]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/flash-sales?status=active&q=Alpha')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Alpha Drop');
});

it('clamps flash sale per_page to 100', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/flash-sales?per_page=200')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});
