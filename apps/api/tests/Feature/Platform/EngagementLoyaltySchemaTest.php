<?php

use App\Models\Customer;
use App\Models\LoyaltyAccount;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\QueryException;

it('allows review for customer and product', function () {
    $customer = Customer::factory()->create();
    $product = Product::factory()->create();

    $review = ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'rating' => 5,
        'status' => 'pending',
    ]);

    expect($review->fresh()->rating)->toBe(5);
});

it('enforces unique wishlist item per wishlist and variant', function () {
    $wishlist = Wishlist::factory()->create();
    $variant = ProductVariant::factory()->create();

    WishlistItem::query()->create([
        'wishlist_id' => $wishlist->id,
        'product_variant_id' => $variant->id,
    ]);

    expect(fn () => WishlistItem::query()->create([
        'wishlist_id' => $wishlist->id,
        'product_variant_id' => $variant->id,
    ]))->toThrow(QueryException::class);
});

it('enforces unique loyalty account per customer', function () {
    $customer = Customer::factory()->create();

    LoyaltyAccount::query()->create([
        'customer_id' => $customer->id,
        'points_balance' => 0,
    ]);

    expect(fn () => LoyaltyAccount::query()->create([
        'customer_id' => $customer->id,
        'points_balance' => 10,
    ]))->toThrow(QueryException::class);
});
