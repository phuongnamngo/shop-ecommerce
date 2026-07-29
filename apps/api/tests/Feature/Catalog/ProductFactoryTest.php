<?php

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Product;
use Illuminate\Database\QueryException;

it('creates a product with exactly one default variant', function () {
    $product = Product::factory()->create();

    expect($product->variants()->count())->toBe(1)
        ->and($product->variants()->where('is_default', true)->count())->toBe(1);
});

it('rejects two options of the same attribute on one variant', function () {
    $product = Product::factory()->create();
    $variant = $product->variants()->first();

    $attribute = Attribute::factory()->create();
    $optA = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);
    $optB = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $variant->attributeOptions()->attach($optA->id, ['attribute_id' => $attribute->id]);

    expect(fn () => $variant->attributeOptions()->attach($optB->id, ['attribute_id' => $attribute->id]))
        ->toThrow(QueryException::class);
});
