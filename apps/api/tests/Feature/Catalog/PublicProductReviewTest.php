<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;

function publicReviewProduct(string $slug): Product
{
    $product = Product::factory()->published()->create(['slug' => $slug]);
    $product->variants()->firstOrFail()->update(['status' => ProductVariant::STATUS_ACTIVE]);

    return $product->refresh();
}

function approveReview(Product $product, int $rating, string $status = ProductReview::STATUS_APPROVED): ProductReview
{
    return ProductReview::factory()->create([
        'customer_id' => Customer::factory(),
        'product_id' => $product->id,
        'rating' => $rating,
        'status' => $status,
    ]);
}

it('exposes null rating on catalog list and detail when there are no approved reviews', function () {
    $product = publicReviewProduct('no-reviews-yet');
    approveReview($product, 5, ProductReview::STATUS_PENDING);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'no-reviews-yet')
        ->assertJsonPath('data.0.rating_avg', null)
        ->assertJsonPath('data.0.rating_count', 0);

    $this->getJson('/api/v1/catalog/products/no-reviews-yet')
        ->assertOk()
        ->assertJsonPath('data.rating_avg', null)
        ->assertJsonPath('data.rating_count', 0);
});

it('lists only approved reviews and hydrates a one-decimal rating average', function () {
    $product = publicReviewProduct('rated-watch');
    approveReview($product, 5);
    approveReview($product, 4);
    approveReview($product, 1, ProductReview::STATUS_PENDING);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonPath('data.0.rating_avg', 4.5)
        ->assertJsonPath('data.0.rating_count', 2);

    $this->getJson('/api/v1/catalog/products/rated-watch')
        ->assertOk()
        ->assertJsonPath('data.rating_avg', 4.5)
        ->assertJsonPath('data.rating_count', 2);

    $this->getJson('/api/v1/catalog/products/rated-watch/reviews')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.rating_avg', 4.5)
        ->assertJsonPath('meta.rating_count', 2);
});

it('keeps histogram and rating meta over all approved reviews not the current page', function () {
    $product = publicReviewProduct('histogram-watch');
    approveReview($product, 5);
    approveReview($product, 5);
    approveReview($product, 3);
    syncPublicCatalogSearch();

    $this->getJson('/api/v1/catalog/products/histogram-watch/reviews?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.rating_count', 3)
        ->assertJsonPath('meta.rating_avg', 4.3)
        ->assertJsonPath('meta.rating_histogram.5', 2)
        ->assertJsonPath('meta.rating_histogram.4', 0)
        ->assertJsonPath('meta.rating_histogram.3', 1)
        ->assertJsonPath('meta.rating_histogram.2', 0)
        ->assertJsonPath('meta.rating_histogram.1', 0);
});
