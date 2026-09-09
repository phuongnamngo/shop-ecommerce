<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

function cartToken(): string
{
    return test()->postJson('/api/v1/cart')->assertCreated()->json('meta.cart_token');
}

it('issues a guest token and rejects missing or unknown guest tokens', function () {
    $this->getJson('/api/v1/cart')->assertUnprocessable()->assertJsonPath('errors.0.code', 'CART_INVALID_TOKEN');
    $this->withHeader('X-Cart-Token', (string) Str::uuid())->getJson('/api/v1/cart')->assertUnprocessable()->assertJsonPath('errors.0.code', 'CART_INVALID_TOKEN');
});

it('creates a guest cart and upserts its variant line at server price', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
    $variant = $product->variants()->first();
    $variant->update(['status' => 'active', 'price' => 123000]);
    $token = cartToken();

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])->assertCreated()->assertJsonPath('data.items.0.qty', 2)->assertJsonPath('data.items.0.unit_price', '123000.00');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])->assertCreated()->assertJsonPath('data.items.0.qty', 3);
});

it('isolates guest carts while mutating lines', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
    $variant = $product->variants()->first();
    $variant->update(['status' => 'active', 'price' => 100000]);
    $firstToken = cartToken();
    $secondToken = cartToken();

    $this->withHeader('X-Cart-Token', $firstToken)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])->assertCreated();
    $this->withHeader('X-Cart-Token', $secondToken)->getJson('/api/v1/cart')->assertOk()->assertJsonCount(0, 'data.items');
    expect(Cart::where('session_id', $firstToken)->firstOrFail()->items()->firstOrFail()->qty)->toBe(2);
});

it('rejects inactive variants and invalid quantities', function () {
    $variant = ProductVariant::factory()->create(['status' => 'inactive']);
    $token = cartToken();

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])->assertUnprocessable();
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 0])->assertUnprocessable();
});

it('updates and removes only lines owned by the supplied guest token', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
    $variant = $product->variants()->first();
    $variant->update(['status' => 'active', 'price' => 100000]);
    $token = cartToken();
    $itemId = $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])->json('data.items.0.id');
    $variant->update(['price' => 125000]);

    $this->withHeader('X-Cart-Token', $token)->patchJson("/api/v1/cart/items/{$itemId}", ['qty' => 4])->assertOk()->assertJsonPath('data.items.0.qty', 4)->assertJsonPath('data.items.0.unit_price', '125000.00');
    $this->withHeader('X-Cart-Token', $token)->deleteJson("/api/v1/cart/items/{$itemId}")->assertOk();
    $this->withHeader('X-Cart-Token', $token)->getJson('/api/v1/cart')->assertJsonCount(0, 'data.items');
});

it('merges a guest cart into the authenticated customer cart without duplicate variants', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
    $variant = $product->variants()->first();
    $variant->update(['status' => 'active', 'price' => 100000]);
    $guestToken = cartToken();
    $this->withHeader('X-Cart-Token', $guestToken)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])->assertCreated();
    $customer = Customer::factory()->create(['status' => 'active']);
    $customerCart = Cart::factory()->create(['customer_id' => $customer->id, 'session_id' => null, 'status' => 'active']);
    $customerCart->items()->create(['product_variant_id' => $variant->id, 'qty' => 1, 'unit_price' => 90000]);

    $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/cart/merge', ['guest_token' => $guestToken])->assertOk();
    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/cart')->assertOk()->assertJsonCount(1, 'data.items')->assertJsonPath('data.items.0.qty', 3)->assertJsonPath('data.items.0.unit_price', '100000.00');
    expect(Cart::where('session_id', $guestToken)->firstOrFail()->status)->toBe('merged');
});

it('includes catalog fields on guest cart lines', function () {
    $product = Product::factory()->published()->create(['name' => 'Watch Alpha', 'slug' => 'watch-alpha']);
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'sku' => 'WA-1', 'price' => 100000]);
    ProductImage::query()->create([
        'product_id' => $product->id,
        'path' => 'catalog/p.jpg',
        'alt' => 'p',
        'position' => 0,
        'is_primary' => true,
    ]);
    $token = cartToken();

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])
        ->assertCreated()
        ->assertJsonPath('data.items.0.product.slug', 'watch-alpha')
        ->assertJsonPath('data.items.0.sku', 'WA-1')
        ->assertJsonPath('data.items.0.attributes', [])
        ->assertJsonPath('data.items.0.thumbnail.alt', 'p');
});

it('maps variant attribute options like PublicProductResource', function () {
    $attribute = Attribute::factory()->create(['name' => 'Color', 'slug' => 'color']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id, 'label' => 'Red']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active']);
    $variant->attributeOptions()->attach($option->id, ['attribute_id' => $attribute->id]);
    $token = cartToken();

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])
        ->assertCreated()
        ->assertJsonPath('data.items.0.attributes.0.name', 'Color')
        ->assertJsonPath('data.items.0.attributes.0.slug', 'color')
        ->assertJsonPath('data.items.0.attributes.0.option.label', 'Red');
});

it('keeps customer cart mutations scoped to the authenticated customer', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
    $variant = $product->variants()->first();
    $variant->update(['status' => 'active', 'price' => 100000]);
    $first = Customer::factory()->create(['status' => 'active']);
    $second = Customer::factory()->create(['status' => 'active']);

    $itemId = $this->actingAs($first, 'customer')->postJson('/api/v1/customer/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])->assertCreated()->json('data.items.0.id');
    $this->actingAs($second, 'customer')->patchJson("/api/v1/customer/cart/items/{$itemId}", ['qty' => 2])->assertUnprocessable()->assertJsonPath('errors.0.code', 'CART_NOT_FOUND');
    $this->actingAs($first, 'customer')->getJson('/api/v1/customer/cart')->assertOk()->assertJsonPath('data.items.0.qty', 1);
});
