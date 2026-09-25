<?php

use App\Models\Product;
use Illuminate\Support\Facades\DB;

it('hydrates listing and suggest without a query per image or variant', function () {
    Product::factory()->published()->create(['name' => 'Hotpath Tee', 'slug' => 'hotpath-tee']);
    Product::factory()->published()->create(['name' => 'Hotpath Polo', 'slug' => 'hotpath-polo']);
    syncPublicCatalogSearch();
    waitForTestingSearchIdle();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/v1/catalog/products?q=hotpath')->assertOk();
    $listing = catalogRelationQueries();
    expect($listing)->toBeLessThanOrEqual(3);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/v1/catalog/search/suggest?q=hotpath')->assertOk();
    $suggest = catalogRelationQueries();
    expect($suggest)->toBeLessThanOrEqual(3);
});

function catalogRelationQueries(): int
{
    return collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $sql) => str_contains($sql, 'product_images') || str_contains($sql, 'product_variants'))
        ->count();
}
