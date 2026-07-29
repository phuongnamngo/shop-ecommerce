<?php

use App\Models\Product;

it('hides draft products from public catalog', function () {
    Product::factory()->create([
        'status' => Product::STATUS_DRAFT,
        'published_at' => null,
    ]);
    Product::factory()->published()->create();

    $this->getJson('/api/v1/catalog/products')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
