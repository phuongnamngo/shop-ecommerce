<?php

use App\Jobs\MakeProductSearchable;
use App\Models\AdminUser;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\CatalogProductService;
use App\Support\CatalogSearchDocument;
use Illuminate\Support\Facades\Queue;

it('reindexes a product when the default variant price changes', function () {
    $product = Product::factory()->published()->create(['slug' => 'sync-price']);
    $product->defaultVariant()->first()?->update(['price' => 10]);

    $document = $product->fresh()->load('defaultVariant')->toSearchableArray();

    expect($document['price'])->toBe(10.0)
        ->and($document['price_bucket'])->toBe('lt_300k');
});

it('unsearchables when the default variant becomes inactive', function () {
    $product = Product::factory()->published()->create(['slug' => 'sync-hide']);
    $product->defaultVariant()->first()?->update([
        'status' => ProductVariant::STATUS_INACTIVE,
    ]);

    expect($product->fresh()->shouldBeSearchable())->toBeFalse();
});

it('builds attribute facet tokens from labels and suffixes id on collisions', function () {
    $product = Product::factory()->published()->create(['slug' => 'facet-tokens']);
    $size = Attribute::factory()->create(['slug' => 'size']);
    $first = AttributeOption::factory()->create(['attribute_id' => $size->id, 'label' => 'M']);
    $second = AttributeOption::factory()->create(['attribute_id' => $size->id, 'label' => 'M']);
    $default = $product->variants()->first();
    $default?->attributeOptions()->attach($first->id, ['attribute_id' => $size->id]);
    $other = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => false,
    ]);
    $other->attributeOptions()->attach($second->id, ['attribute_id' => $size->id]);

    expect(CatalogSearchDocument::attributeFacets($product->fresh()))
        ->toEqualCanonicalizing([
            'size:m-'.$first->id,
            'size:m-'.$second->id,
        ]);
});

it('unsearchables when a published product is unpublished via catalog service', function () {
    $product = Product::factory()->published()->create([
        'name' => 'UniqueUnpublishNeedle',
        'slug' => 'unique-unpublish-needle',
    ]);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products?q=UniqueUnpublishNeedle')
        ->assertOk()
        ->assertJsonPath('meta.total', 1);

    $admin = AdminUser::factory()->create();
    app(CatalogProductService::class)->update($product, [
        'status' => Product::STATUS_INACTIVE,
    ], $admin->id);
    waitForTestingSearchIdle();

    $this->getJson('/api/v1/catalog/products?q=UniqueUnpublishNeedle')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('dispatches delayed searchable for a future published_at', function () {
    Queue::fake();

    Product::factory()->create([
        'status' => Product::STATUS_ACTIVE,
        'published_at' => now()->addHour(),
        'slug' => 'coming-soon',
    ]);

    Queue::assertPushed(MakeProductSearchable::class);
});
