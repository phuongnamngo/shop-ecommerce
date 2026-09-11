<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Support\ErrorCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function reviewPublishedProduct(): Product
{
    $product = Product::factory()->published()->create();
    $product->variants()->firstOrFail()->update(['status' => ProductVariant::STATUS_ACTIVE]);

    return $product->refresh();
}

function purchaseProduct(Customer $customer, Product $product, string $status = 'completed'): Order
{
    $variant = $product->variants()->firstOrFail();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => $status,
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'name' => 'Item',
        'qty' => 1,
        'unit_price' => $variant->price,
        'line_total' => $variant->price,
    ]);

    return $order;
}

it('rejects guest review routes', function () {
    $this->getJson('/api/v1/customer/reviews')->assertUnauthorized();
    $this->postJson('/api/v1/customer/reviews', ['product_id' => 1, 'rating' => 5])->assertUnauthorized();
    $this->getJson('/api/v1/customer/products/1/review-eligibility')->assertUnauthorized();
});

it('returns an empty customer review list', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/reviews')
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('returns eligibility reasons including purchased soft-deleted variants', function () {
    $customer = Customer::factory()->create();
    $unbought = reviewPublishedProduct();
    $paid = reviewPublishedProduct();
    $ok = reviewPublishedProduct();
    $pendingProduct = reviewPublishedProduct();
    $rejectedProduct = reviewPublishedProduct();
    $approvedProduct = reviewPublishedProduct();

    purchaseProduct($customer, $paid, 'paid');
    purchaseProduct($customer, $ok, 'completed');
    $ok->variants()->firstOrFail()->delete();
    purchaseProduct($customer, $pendingProduct, 'shipped');
    purchaseProduct($customer, $rejectedProduct, 'completed');
    purchaseProduct($customer, $approvedProduct, 'completed');

    ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $pendingProduct->id,
        'status' => ProductReview::STATUS_PENDING,
        'rating' => 4,
    ]);
    ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $rejectedProduct->id,
        'status' => ProductReview::STATUS_REJECTED,
        'rating' => 2,
    ]);
    ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $approvedProduct->id,
        'status' => ProductReview::STATUS_APPROVED,
        'rating' => 5,
    ]);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/products/'.$unbought->id.'/review-eligibility')
        ->assertOk()
        ->assertJsonPath('data.eligible', false)
        ->assertJsonPath('data.reason', 'not_purchased')
        ->assertJsonPath('data.existing_review', null);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/products/'.$paid->id.'/review-eligibility')
        ->assertOk()
        ->assertJsonPath('data.reason', 'not_purchased')
        ->assertJsonPath('data.eligible', false);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/products/'.$ok->id.'/review-eligibility')
        ->assertOk()
        ->assertJsonPath('data.eligible', true)
        ->assertJsonPath('data.reason', 'ok')
        ->assertJsonPath('data.existing_review', null);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/products/'.$pendingProduct->id.'/review-eligibility')
        ->assertOk()
        ->assertJsonPath('data.eligible', false)
        ->assertJsonPath('data.reason', 'pending')
        ->assertJsonPath('data.existing_review.status', 'pending');

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/products/'.$rejectedProduct->id.'/review-eligibility')
        ->assertOk()
        ->assertJsonPath('data.reason', 'rejected');

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/products/'.$approvedProduct->id.'/review-eligibility')
        ->assertOk()
        ->assertJsonPath('data.reason', 'approved');
});

it('rejects posting a review for a paid order', function () {
    $customer = Customer::factory()->create();
    $product = reviewPublishedProduct();
    purchaseProduct($customer, $product, 'paid');

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', ErrorCode::REVIEW_NOT_ELIGIBLE);
});

it('creates a pending review once then 409', function () {
    Storage::fake('public');
    $customer = Customer::factory()->create();
    $product = reviewPublishedProduct();
    purchaseProduct($customer, $product, 'completed');

    $created = $this->actingAs($customer, 'customer')
        ->post('/api/v1/customer/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'body' => 'Great watch',
            'images' => [
                UploadedFile::fake()->image('one.jpg', 400, 400),
            ],
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.status', ProductReview::STATUS_PENDING)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.images.0.url', fn (string $url) => str_contains($url, 'reviews/'))
        ->json('data.id');

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/reviews', [
            'product_id' => $product->id,
            'rating' => 4,
        ])
        ->assertStatus(409)
        ->assertJsonPath('errors.0.code', ErrorCode::REVIEW_ALREADY_EXISTS);

    $this->getJson('/api/v1/catalog/products/'.$product->slug.'/reviews')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    expect($created)->toBeInt();
});

it('rejects a fourth review image', function () {
    Storage::fake('public');
    $customer = Customer::factory()->create();
    $product = reviewPublishedProduct();
    purchaseProduct($customer, $product, 'completed');

    $this->actingAs($customer, 'customer')
        ->post('/api/v1/customer/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
            'images' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
                UploadedFile::fake()->image('c.jpg'),
                UploadedFile::fake()->image('d.jpg'),
            ],
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', ErrorCode::VALIDATION_FAILED);
});

it('patches rejected reviews to pending and forbids patching approved', function () {
    $customer = Customer::factory()->create();
    $rejectedProduct = reviewPublishedProduct();
    $approvedProduct = reviewPublishedProduct();
    purchaseProduct($customer, $rejectedProduct, 'completed');
    purchaseProduct($customer, $approvedProduct, 'completed');

    $rejected = ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $rejectedProduct->id,
        'status' => ProductReview::STATUS_REJECTED,
        'rating' => 1,
        'body' => 'old',
    ]);
    $approved = ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $approvedProduct->id,
        'status' => ProductReview::STATUS_APPROVED,
        'rating' => 5,
    ]);

    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/reviews/'.$rejected->id, [
            'rating' => 4,
            'body' => 'updated',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', ProductReview::STATUS_PENDING)
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.body', 'updated');

    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/reviews/'.$approved->id, [
            'rating' => 3,
        ])
        ->assertStatus(409)
        ->assertJsonPath('errors.0.code', ErrorCode::REVIEW_NOT_EDITABLE);
});

it('masks the author name on public reviews and hides draft products', function () {
    $customer = Customer::factory()->create(['name' => 'Nguyễn Văn An']);
    $product = reviewPublishedProduct();
    purchaseProduct($customer, $product, 'completed');
    ProductReview::factory()->create([
        'customer_id' => $customer->id,
        'product_id' => $product->id,
        'status' => ProductReview::STATUS_APPROVED,
        'rating' => 5,
        'body' => 'Loved it',
    ]);

    $this->getJson('/api/v1/catalog/products/'.$product->slug.'/reviews')
        ->assertOk()
        ->assertJsonPath('data.0.author_name', 'Nguyễn Văn A.')
        ->assertJsonPath('data.0.rating', 5)
        ->assertJsonMissingPath('data.0.customer')
        ->assertJsonMissingPath('data.0.email');

    $draft = Product::factory()->create([
        'status' => Product::STATUS_DRAFT,
        'published_at' => null,
        'slug' => 'draft-review-product',
    ]);

    $this->getJson('/api/v1/catalog/products/'.$draft->slug.'/reviews')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', ErrorCode::CATALOG_NOT_FOUND);
});
