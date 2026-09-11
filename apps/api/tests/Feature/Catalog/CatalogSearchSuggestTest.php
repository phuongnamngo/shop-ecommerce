<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Config;
use Meilisearch\Client;

it('returns empty suggest groups without calling Meili when q is shorter than 2', function () {
    Product::factory()->published()->create(['name' => 'Áo thun', 'slug' => 'ao-thun']);

    $this->getJson('/api/v1/catalog/search/suggest?q=a')
        ->assertOk()
        ->assertJsonPath('data.products', [])
        ->assertJsonPath('data.categories', [])
        ->assertJsonPath('data.brands', []);
});

it('returns mixed product, category, and brand suggestions', function () {
    $brand = Brand::factory()->create(['name' => 'ÁoCo', 'slug' => 'aoco']);
    $category = Category::factory()->create(['name' => 'Áo thun nam', 'slug' => 'ao-thun-nam']);
    Product::factory()->published()->create([
        'name' => 'Áo polo navy',
        'slug' => 'ao-polo-navy',
        'brand_id' => $brand->id,
    ]);
    syncPublicCatalogSearch();
    $brand->searchableSync();
    $category->searchableSync();
    waitForTestingSearchIdle();

    $this->getJson('/api/v1/catalog/search/suggest?q='.urlencode('Áo'))
        ->assertOk()
        ->assertJsonPath('data.products.0.slug', 'ao-polo-navy')
        ->assertJsonPath('data.categories.0.slug', 'ao-thun-nam')
        ->assertJsonPath('data.brands.0.slug', 'aoco');
});

it('returns CATALOG_SEARCH_UNAVAILABLE for suggest when Meili is down', function () {
    app()->forgetInstance(Client::class);
    Config::set('scout.meilisearch.host', 'http://127.0.0.1:1');

    $this->getJson('/api/v1/catalog/search/suggest?q=ao')
        ->assertStatus(503)
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_SEARCH_UNAVAILABLE]);

    Config::set('scout.meilisearch.host', 'http://meilisearch:7700');
    app()->forgetInstance(Client::class);
});
