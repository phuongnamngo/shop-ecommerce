<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use App\Support\CatalogPublicCache;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('stores a versioned payload for 60 seconds and bumps the version', function () {
    $cache = app(CatalogPublicCache::class);
    $cache->remember('categories', fn () => ['tree' => 'a']);

    expect(Cache::get('catalog.public.v1.categories'))->toBe(['tree' => 'a'])
        ->and(Cache::has('catalog.public.version'))->toBeFalse();

    $this->travel(61)->seconds();

    expect(Cache::get('catalog.public.v1.categories'))->toBeNull();

    $cache->bump();

    expect($cache->version())->toBe(2);
});

it('serves a second public product read from cache', function () {
    $product = Product::factory()->published()->create(['slug' => 'cached-tee', 'name' => 'Cached Tee']);

    $this->getJson('/api/v1/catalog/products/cached-tee')
        ->assertOk()
        ->assertJsonPath('data.slug', 'cached-tee')
        ->assertJsonPath('data.name', 'Cached Tee');

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->getJson('/api/v1/catalog/products/cached-tee')
        ->assertOk()
        ->assertJsonPath('data.name', 'Cached Tee');

    $productQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'products'));

    expect($productQueries)->toHaveCount(0);
});

it('shows the updated product name after an admin patch', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $product = Product::factory()->published()->create(['slug' => 'rename-tee', 'name' => 'Old Name']);
    $this->getJson('/api/v1/catalog/products/rename-tee')->assertOk()->assertJsonPath('data.name', 'Old Name');

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$product->id, ['name' => 'New Name'])
        ->assertOk();

    $this->getJson('/api/v1/catalog/products/rename-tee')->assertOk()->assertJsonPath('data.name', 'New Name');
});

it('shows the updated flash sale price on the next product read', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $product = Product::factory()->published()->create(['slug' => 'sale-tee']);
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => 120000]);
    $admin = catalogAdmin();
    $created = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/promotions/flash-sales', [
            'name' => 'Noon drop',
            'starts_at' => now()->subHour()->toIso8601String(),
            'ends_at' => now()->addHours(3)->toIso8601String(),
            'status' => 'active',
            'items' => [
                ['product_variant_id' => $variant->id, 'sale_price' => 79000, 'qty_cap' => 20],
            ],
        ])
        ->assertCreated();

    $this->getJson('/api/v1/catalog/products/sale-tee')
        ->assertOk()
        ->assertJsonPath('data.variants.0.price', '79000.00');

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/promotions/flash-sales/'.$created->json('data.id'), [
            'items' => [
                ['product_variant_id' => $variant->id, 'sale_price' => 55000, 'qty_cap' => 20],
            ],
        ])
        ->assertOk();

    $this->getJson('/api/v1/catalog/products/sale-tee')
        ->assertOk()
        ->assertJsonPath('data.variants.0.price', '55000.00');
});

it('shows the updated category name after an admin patch', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $category = Category::factory()->create(['status' => Category::STATUS_ACTIVE, 'name' => 'Old Tree']);
    $this->getJson('/api/v1/catalog/categories')->assertOk()->assertJsonFragment(['name' => 'Old Tree']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/categories/'.$category->id, ['name' => 'New Tree'])
        ->assertOk();

    $this->getJson('/api/v1/catalog/categories')
        ->assertOk()
        ->assertJsonFragment(['name' => 'New Tree'])
        ->assertJsonMissing(['name' => 'Old Tree']);
});

it('does not bump the catalog cache when checkout changes stock', function () {
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    $province = GeoProvince::query()->create(['code' => 'PC', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DC', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WC', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'checkout-cache', 'name' => 'Checkout', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'min_order_amount' => 100000, 'price' => 30000]);
    $warehouse = Warehouse::query()->create(['code' => 'DEFAULT', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => 150000]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 5, 'qty_reserved' => 0]);
    Cache::put('catalog.public.version', 4);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])
        ->assertCreated();

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PC', 'district_code' => 'DC', 'ward_code' => 'WC', 'address_line' => 'Road'],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'cod',
    ])->assertCreated();

    expect(Cache::get('catalog.public.version'))->toBe(4);
});

it('does not cache a missing product slug', function () {
    $this->getJson('/api/v1/catalog/products/missing-slug')->assertNotFound();

    expect(Cache::get('catalog.public.version'))->toBeNull()
        ->and(Cache::has('catalog.public.v1.product.missing-slug'))->toBeFalse();
});

it('caches each brand page separately', function () {
    Brand::factory()->create(['status' => Brand::STATUS_ACTIVE, 'name' => 'Alpha']);
    Brand::factory()->create(['status' => Brand::STATUS_ACTIVE, 'name' => 'Beta']);

    $first = $this->getJson('/api/v1/catalog/brands?page=1&per_page=1')->assertOk();
    DB::flushQueryLog();
    DB::enableQueryLog();
    $again = $this->getJson('/api/v1/catalog/brands?page=1&per_page=1')->assertOk();
    $brandQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'brands'));
    expect($brandQueries)->toHaveCount(0)
        ->and($again->json('data.0.id'))->toBe($first->json('data.0.id'));

    $secondPage = $this->getJson('/api/v1/catalog/brands?page=2&per_page=1')->assertOk();
    expect($secondPage->json('data.0.id'))->not->toBe($first->json('data.0.id'));
});

it('serves a second category tree read from cache', function () {
    Category::factory()->create(['status' => Category::STATUS_ACTIVE, 'name' => 'Watches']);

    $this->getJson('/api/v1/catalog/categories')->assertOk()->assertJsonFragment(['name' => 'Watches']);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/v1/catalog/categories')->assertOk()->assertJsonFragment(['name' => 'Watches']);

    $categoryQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'categories'));

    expect($categoryQueries)->toHaveCount(0);
});

it('does not touch the catalog cache when listing products', function () {
    Cache::flush();

    $this->getJson('/api/v1/catalog/products')->assertOk();

    expect(Cache::get('catalog.public.version'))->toBeNull();
});
