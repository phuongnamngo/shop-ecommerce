<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Meilisearch\Client;

it('hides draft products from public catalog', function () {
    Product::factory()->create([
        'status' => Product::STATUS_DRAFT,
        'published_at' => null,
    ]);
    Product::factory()->published()->create();
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonMissingPath('data.0.code')
        ->assertJsonMissingPath('data.0.created_by');
});

it('hides unpublished, future, and inactive-default products', function () {
    Product::factory()->create([
        'status' => Product::STATUS_ACTIVE,
        'published_at' => null,
    ]);
    Product::factory()->create([
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->addDay(),
    ]);
    $inactiveDefault = Product::factory()->published()->create();
    $inactiveDefault->defaultVariant()->first()?->update([
        'status' => ProductVariant::STATUS_INACTIVE,
    ]);
    Product::factory()->published()->create(['slug' => 'visible-one']);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'visible-one');
});

it('lists a product created active without published_at', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products', [
            'name' => 'Live Tee',
            'status' => Product::STATUS_ACTIVE,
            'variants' => [
                ['sku' => 'SKU-LIVE', 'price' => 99000, 'is_default' => true],
            ],
        ])
        ->assertCreated();

    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', Str::slug('Live Tee'));
});

it('filters by brand and case-insensitive name', function () {
    $brand = Brand::factory()->create();
    Product::factory()->published()->create([
        'name' => 'AbC Watch',
        'slug' => 'abc-watch',
        'brand_id' => $brand->id,
    ]);
    Product::factory()->published()->create(['name' => 'Other', 'slug' => 'other']);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?brand_id='.$brand->id.'&q=abc')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'abc-watch');
});

it('filters by category including descendants but not siblings', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $sibling = Category::factory()->create();

    $inChild = Product::factory()->published()->create(['slug' => 'in-child']);
    $inSibling = Product::factory()->published()->create(['slug' => 'in-sibling']);
    $inChild->categories()->attach($child->id);
    $inSibling->categories()->attach($sibling->id);
    $inChild->searchableSync();
    $inSibling->searchableSync();
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?category_id='.$parent->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'in-child');
});

it('sorts public products by price', function () {
    $cheap = Product::factory()->published()->create(['slug' => 'cheap']);
    $pricey = Product::factory()->published()->create(['slug' => 'pricey']);
    $cheap->defaultVariant()->first()?->update(['price' => 10]);
    $pricey->defaultVariant()->first()?->update(['price' => 90]);
    $cheap->unsetRelation('defaultVariant')->searchableSync();
    $pricey->unsetRelation('defaultVariant')->searchableSync();
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?sort=price_asc')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'cheap')
        ->assertJsonPath('data.1.slug', 'pricey');

    $this->getJson('/api/v1/catalog/products?sort=price_desc')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'pricey')
        ->assertJsonPath('data.1.slug', 'cheap');
});

it('returns public product detail by slug and hides inactive brand', function () {
    $brand = Brand::factory()->create(['status' => Brand::STATUS_INACTIVE]);
    $category = Category::factory()->create(['status' => Category::STATUS_ACTIVE]);
    $hiddenParent = Category::factory()->create(['status' => Category::STATUS_INACTIVE]);
    $orphan = Category::factory()->create([
        'parent_id' => $hiddenParent->id,
        'status' => Category::STATUS_ACTIVE,
    ]);
    $attribute = Attribute::factory()->create(['name' => 'Color', 'slug' => 'color']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'label' => 'Red']);

    $product = Product::factory()->published()->create([
        'slug' => 'pdp-tee',
        'brand_id' => $brand->id,
        'description' => 'Soft cotton',
    ]);
    $product->categories()->attach([$category->id, $orphan->id]);
    $variant = $product->variants()->first();
    $variant->attributeOptions()->attach($option->id, ['attribute_id' => $attribute->id]);
    $product->searchableSync();

    $this->getJson('/api/v1/catalog/products/pdp-tee')
        ->assertOk()
        ->assertJsonPath('data.slug', 'pdp-tee')
        ->assertJsonPath('data.brand', null)
        ->assertJsonPath('data.description', 'Soft cotton')
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonPath('data.categories.0.id', $category->id)
        ->assertJsonPath('data.variants.0.id', $variant->id)
        ->assertJsonPath('data.variants.0.attributes.0.option.label', 'Red')
        ->assertJsonMissingPath('data.code');

    $this->getJson('/api/v1/catalog/products/missing-slug')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_NOT_FOUND]);
});

it('filters by price_bucket at 300000 and 500000 boundaries', function () {
    $low = Product::factory()->published()->create(['slug' => 'low']);
    $mid = Product::factory()->published()->create(['slug' => 'mid']);
    $high = Product::factory()->published()->create(['slug' => 'high']);
    $low->defaultVariant()->first()?->update(['price' => 299999.99]);
    $mid->defaultVariant()->first()?->update(['price' => 300000]);
    $high->defaultVariant()->first()?->update(['price' => 500000]);
    $low->unsetRelation('defaultVariant')->searchableSync();
    $mid->unsetRelation('defaultVariant')->searchableSync();
    $high->unsetRelation('defaultVariant')->searchableSync();
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?price_bucket=lt_300k')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'low')
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/v1/catalog/products?price_bucket=300_500k')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'mid')
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/v1/catalog/products?price_bucket=500k_plus')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'high')
        ->assertJsonCount(1, 'data');
});

it('rejects unknown price_bucket with VALIDATION_FAILED', function () {
    $this->getJson('/api/v1/catalog/products?price_bucket=nope')
        ->assertStatus(422)
        ->assertJsonFragment(['code' => ErrorCode::VALIDATION_FAILED]);
});

it('rejects malformed attribute_facets without a colon', function () {
    $this->getJson('/api/v1/catalog/products?attribute_facets[]=nocolon')
        ->assertStatus(422)
        ->assertJsonFragment(['code' => ErrorCode::VALIDATION_FAILED]);
});

it('drops unknown attribute_facets tokens instead of 404', function () {
    Product::factory()->published()->create(['slug' => 'plain']);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?'.http_build_query([
        'attribute_facets' => ['size:zzzz'],
    ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'plain');
});

it('applies attribute_facets as OR within a slug and AND across slugs', function () {
    $size = Attribute::factory()->create(['name' => 'Size', 'slug' => 'size']);
    $color = Attribute::factory()->create(['name' => 'Color', 'slug' => 'color']);
    $m = AttributeOption::factory()->create(['attribute_id' => $size->id, 'label' => 'M']);
    $l = AttributeOption::factory()->create(['attribute_id' => $size->id, 'label' => 'L']);
    $red = AttributeOption::factory()->create(['attribute_id' => $color->id, 'label' => 'Red']);

    $mRed = Product::factory()->published()->create(['slug' => 'm-red']);
    $lRed = Product::factory()->published()->create(['slug' => 'l-red']);
    $mOnly = Product::factory()->published()->create(['slug' => 'm-only']);

    $mRed->variants()->first()->attributeOptions()->attach($m->id, ['attribute_id' => $size->id]);
    $mRed->variants()->first()->attributeOptions()->attach($red->id, ['attribute_id' => $color->id]);
    $lRed->variants()->first()->attributeOptions()->attach($l->id, ['attribute_id' => $size->id]);
    $lRed->variants()->first()->attributeOptions()->attach($red->id, ['attribute_id' => $color->id]);
    $mOnly->variants()->first()->attributeOptions()->attach($m->id, ['attribute_id' => $size->id]);
    $mRed->unsetRelation('variants')->searchableSync();
    $lRed->unsetRelation('variants')->searchableSync();
    $mOnly->unsetRelation('variants')->searchableSync();
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?'.http_build_query([
        'attribute_facets' => ['size:m', 'size:l'],
    ]))
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->getJson('/api/v1/catalog/products?'.http_build_query([
        'attribute_facets' => ['size:m', 'color:red'],
    ]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'm-red');
});

it('returns facet counts in listing meta', function () {
    $brand = Brand::factory()->create(['name' => 'Facet Brand', 'slug' => 'facet-brand']);
    $product = Product::factory()->published()->create([
        'slug' => 'facet-tee',
        'brand_id' => $brand->id,
    ]);
    $product->defaultVariant()->first()?->update(['price' => 100000]);
    $product->unsetRelation('defaultVariant')->searchableSync();
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonPath('meta.facets.price_buckets.0.token', 'lt_300k')
        ->assertJsonPath('meta.facets.brands.0.slug', 'facet-brand')
        ->assertJsonPath('meta.facets.brands.0.count', 1);
});

it('returns an empty page with 200 when nothing matches', function () {
    Product::factory()->published()->create(['name' => 'Shirt', 'slug' => 'shirt']);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?q=zzzznotfound')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

it('returns CATALOG_SEARCH_UNAVAILABLE when Meili is down', function () {
    app()->forgetInstance(Client::class);
    Config::set('scout.meilisearch.host', 'http://127.0.0.1:1');

    $this->getJson('/api/v1/catalog/products')
        ->assertStatus(503)
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_SEARCH_UNAVAILABLE]);

    Config::set('scout.meilisearch.host', 'http://meilisearch:7700');
    app()->forgetInstance(Client::class);
});
