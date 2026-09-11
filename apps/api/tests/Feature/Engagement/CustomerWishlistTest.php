<?php

use App\Models\Customer;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ErrorCode;

function wishlistPublishedVariant(int $listPrice = 100000): ProductVariant
{
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => ProductVariant::STATUS_ACTIVE, 'price' => $listPrice]);

    return $variant->refresh();
}

it('rejects guest wishlist', function () {
    $this->getJson('/api/v1/customer/wishlist')->assertUnauthorized();
    $this->postJson('/api/v1/customer/wishlist/items', ['product_variant_id' => 1])->assertUnauthorized();
});

it('creates a Default wishlist on first get', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/wishlist')
        ->assertOk()
        ->assertJsonPath('data.name', 'Default')
        ->assertJsonPath('data.items', []);
});

it('adds a variant once then 409', function () {
    $customer = Customer::factory()->create();
    $variant = wishlistPublishedVariant();

    $created = $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/wishlist/items', ['product_variant_id' => $variant->id])
        ->assertCreated()
        ->assertJsonPath('data.items.0.product_variant_id', $variant->id)
        ->assertJsonPath('data.items.0.price', '100000.00')
        ->json('data.items.0.id');

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/wishlist/items', ['product_variant_id' => $variant->id])
        ->assertStatus(409)
        ->assertJsonPath('errors.0.code', ErrorCode::WISHLIST_ITEM_EXISTS);

    $this->actingAs($customer, 'customer')
        ->deleteJson('/api/v1/customer/wishlist/items/'.$created)
        ->assertOk();

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/wishlist')
        ->assertOk()
        ->assertJsonPath('data.items', []);
});

it('rejects inactive or deleted variants', function () {
    $customer = Customer::factory()->create();
    $inactive = wishlistPublishedVariant();
    $inactive->update(['status' => ProductVariant::STATUS_INACTIVE]);

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/wishlist/items', ['product_variant_id' => $inactive->id])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', ErrorCode::VALIDATION_FAILED);

    $deleted = wishlistPublishedVariant();
    $deleted->delete();

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/wishlist/items', ['product_variant_id' => $deleted->id])
        ->assertUnprocessable();
});

it('returns flash sale unit price on wishlist items', function () {
    $customer = Customer::factory()->create();
    $variant = wishlistPublishedVariant(120000);
    FlashSaleItem::factory()->create([
        'flash_sale_id' => FlashSale::factory()->create([
            'status' => FlashSale::STATUS_ACTIVE,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(2),
        ]),
        'product_variant_id' => $variant->id,
        'sale_price' => 79000,
    ]);

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/wishlist/items', ['product_variant_id' => $variant->id])
        ->assertCreated()
        ->assertJsonPath('data.items.0.price', '79000.00');
});

it('returns 404 when deleting a missing wishlist item', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->deleteJson('/api/v1/customer/wishlist/items/999999')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', ErrorCode::WISHLIST_ITEM_NOT_FOUND);
});
