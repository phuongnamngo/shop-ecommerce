<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('forbids deleting the last variant', function () {
    $product = Product::factory()->create();
    $variant = $product->variants()->first();

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/catalog/products/'.$product->id.'/variants/'.$variant->id)
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_VARIANT_INVARIANT]);
});

it('swaps the default variant without unique errors', function () {
    $product = Product::factory()->create();
    $current = $product->variants()->first();
    $next = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'is_default' => false,
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$product->id.'/variants/'.$next->id, [
            'is_default' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.is_default', true);

    expect($current->refresh()->is_default)->toBeFalse()
        ->and($next->refresh()->is_default)->toBeTrue();
});

it('rejects unsetting the only default variant', function () {
    $product = Product::factory()->create();
    $variant = $product->variants()->first();

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/catalog/products/'.$product->id.'/variants/'.$variant->id, [
            'is_default' => false,
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CATALOG_VARIANT_INVARIANT]);
});

it('creates an additional variant', function () {
    $product = Product::factory()->create();

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/catalog/products/'.$product->id.'/variants', [
            'sku' => 'SKU-EXTRA',
            'price' => 250000,
            'is_default' => false,
        ])
        ->assertCreated()
        ->assertJsonPath('data.sku', 'SKU-EXTRA')
        ->assertJsonPath('data.is_default', false);
});
