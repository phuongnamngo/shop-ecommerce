<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function adminReviewProduct(string $slug = 'admin-review-watch'): Product
{
    return Product::factory()->published()->create(['slug' => $slug, 'name' => 'Admin Review Watch']);
}

it('allows staff to list reviews but forbids PATCH', function () {
    $product = adminReviewProduct();
    $review = ProductReview::factory()->create([
        'product_id' => $product->id,
        'status' => ProductReview::STATUS_PENDING,
        'rating' => 4,
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/reviews')
        ->assertOk()
        ->assertJsonPath('data.0.id', $review->id)
        ->assertJsonPath('data.0.customer.email', $review->customer->email);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->patchJson('/api/v1/admin/reviews/'.$review->id, ['status' => 'approved'])
        ->assertForbidden();
});

it('filters admin reviews by status, product, and customer email', function () {
    $watch = adminReviewProduct('filter-watch');
    $other = Product::factory()->published()->create(['slug' => 'other-watch', 'name' => 'Other']);
    $target = Customer::factory()->create(['email' => 'reviewer.target@example.com', 'name' => 'Target Reviewer']);
    $pending = ProductReview::factory()->create([
        'customer_id' => $target->id,
        'product_id' => $watch->id,
        'status' => ProductReview::STATUS_PENDING,
        'rating' => 5,
    ]);
    ProductReview::factory()->create([
        'product_id' => $other->id,
        'status' => ProductReview::STATUS_APPROVED,
        'rating' => 3,
    ]);

    $admin = catalogAdmin();

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/reviews?status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/reviews?product_id='.$watch->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/reviews?q=reviewer.target@example.com')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.customer.email', 'reviewer.target@example.com');
});

it('approves a review onto the public list then hides it when rejected', function () {
    $product = adminReviewProduct('moderate-watch');
    $customer = Customer::factory()->create(['name' => 'Nguyễn Văn An', 'email' => 'an.reviewer@example.com']);
    $review = ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'status' => ProductReview::STATUS_PENDING,
        'rating' => 5,
        'body' => 'Nice',
    ]);

    $this->getJson('/api/v1/catalog/products/'.$product->slug.'/reviews')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/reviews/'.$review->id)
        ->assertOk()
        ->assertJsonPath('data.customer.email', 'an.reviewer@example.com')
        ->assertJsonPath('data.customer.name', 'Nguyễn Văn An');

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/reviews/'.$review->id, ['status' => 'approved'])
        ->assertOk()
        ->assertJsonPath('data.status', ProductReview::STATUS_APPROVED);

    $this->getJson('/api/v1/catalog/products/'.$product->slug.'/reviews')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.author_name', 'Nguyễn Văn A.')
        ->assertJsonMissingPath('data.0.customer');

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/reviews/'.$review->id, ['status' => 'rejected'])
        ->assertOk()
        ->assertJsonPath('data.status', ProductReview::STATUS_REJECTED);

    $this->getJson('/api/v1/catalog/products/'.$product->slug.'/reviews')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
