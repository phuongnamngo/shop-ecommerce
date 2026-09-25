<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

it('does not update cart line prices when the stored price already matches', function () {
    $product = Product::factory()->create(['status' => 'active', 'published_at' => now()]);
    $first = $product->variants()->firstOrFail();
    $first->update(['status' => 'active', 'price' => 150000]);
    $second = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => 'active',
        'price' => 180000,
        'is_default' => false,
    ]);
    $token = $this->postJson('/api/v1/cart')->assertCreated()->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $first->id, 'qty' => 1])
        ->assertCreated();
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $second->id, 'qty' => 1])
        ->assertCreated();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->withHeader('X-Cart-Token', $token)->getJson('/api/v1/cart')->assertOk();

    $updates = collect(DB::getQueryLog())->pluck('query')->filter(
        fn (string $sql) => str_contains(strtolower($sql), 'update') && str_contains($sql, 'cart_items')
    );
    expect($updates)->toHaveCount(0);
});
