<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

it('hides draft products from public catalog', function () {
    Product::factory()->create([
        'status' => Product::STATUS_DRAFT,
        'published_at' => null,
    ]);
    Product::factory()->published()->create();

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
    $inactiveDefault->variants()->where('is_default', true)->update([
        'status' => ProductVariant::STATUS_INACTIVE,
    ]);
    Product::factory()->published()->create(['slug' => 'visible-one']);

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

    $this->getJson('/api/v1/catalog/products?category_id='.$parent->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'in-child');
});

it('sorts public products by price', function () {
    $cheap = Product::factory()->published()->create(['slug' => 'cheap']);
    $pricey = Product::factory()->published()->create(['slug' => 'pricey']);
    $cheap->variants()->where('is_default', true)->update(['price' => 10]);
    $pricey->variants()->where('is_default', true)->update(['price' => 90]);

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
