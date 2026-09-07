<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function catalogProductPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Demo Tee',
        'variants' => [
            ['sku' => 'SKU-DEFAULT', 'price' => 100000, 'is_default' => true],
            ['sku' => 'SKU-ALT', 'price' => 110000, 'is_default' => false],
        ],
    ], $overrides);
}

it('rejects create with zero variants', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload(['variants' => []]))
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_VARIANT_INVARIANT]);
});

it('rejects create with two default variants', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload([
            'variants' => [
                ['sku' => 'SKU-A', 'price' => 1, 'is_default' => true],
                ['sku' => 'SKU-B', 'price' => 2, 'is_default' => true],
            ],
        ]))
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_VARIANT_INVARIANT]);
});

it('creates a product with two variants', function () {
    $category = Category::factory()->create();

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload([
            'category_ids' => [$category->id],
        ]))
        ->assertCreated()
        ->assertJsonPath('data.slug', Str::slug('Demo Tee'))
        ->assertJsonPath('data.status', Product::STATUS_DRAFT)
        ->assertJsonPath('data.code', fn ($code) => is_string($code) && $code !== '')
        ->assertJsonCount(2, 'data.variants')
        ->assertJsonCount(1, 'data.categories');
});

it('rejects a duplicate sku', function () {
    ProductVariant::factory()->create(['sku' => 'SKU-TAKEN']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload([
            'variants' => [
                ['sku' => 'SKU-TAKEN', 'price' => 1, 'is_default' => true],
            ],
        ]))
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_SKU_TAKEN, 'field' => 'sku']);
});

it('keeps slug when patching name only', function () {
    $product = Product::factory()->create(['name' => 'Old', 'slug' => 'keep-me']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$product->id, ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.slug', 'keep-me');
});

it('syncs category_ids on patch', function () {
    $product = Product::factory()->create();
    $keep = Category::factory()->create();
    $drop = Category::factory()->create();
    $add = Category::factory()->create();
    $product->categories()->attach([$keep->id, $drop->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$product->id, [
            'category_ids' => [$keep->id, $add->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.categories.0.id', $keep->id)
        ->assertJsonCount(2, 'data.categories');
});

it('soft-deletes a product then allows reused sku and slug', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload([
            'slug' => 'reuse-me',
            'variants' => [
                ['sku' => 'SKU-REUSE', 'price' => 1, 'is_default' => true],
            ],
        ]))
        ->assertCreated();

    $id = Product::query()->where('slug', 'reuse-me')->value('id');

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/catalog/products/'.$id)
        ->assertOk()
        ->assertJsonPath('data', null);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload([
            'slug' => 'reuse-me',
            'variants' => [
                ['sku' => 'SKU-REUSE', 'price' => 2, 'is_default' => true],
            ],
        ]))
        ->assertCreated()
        ->assertJsonPath('data.slug', 'reuse-me');
});

it('forbids staff from creating a product', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/catalog/products', catalogProductPayload())
        ->assertForbidden();
});

it('filters admin list by exact category_id', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $inParent = Product::factory()->create();
    $inChild = Product::factory()->create();
    $inParent->categories()->attach($parent->id);
    $inChild->categories()->attach($child->id);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/catalog/products?category_id='.$parent->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $inParent->id);
});

it('filters admin list by brand_id', function () {
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);
    Product::factory()->create();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/catalog/products?brand_id='.$brand->id)
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
