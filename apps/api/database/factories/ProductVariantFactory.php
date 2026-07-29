<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'code' => (string) Str::ulid(),
            'product_id' => Product::factory(),
            'sku' => 'SKU-'.Str::upper(Str::random(10)),
            'barcode' => null,
            'price' => 100000,
            'compare_at_price' => null,
            'is_default' => false,
            'status' => ProductVariant::STATUS_ACTIVE,
        ];
    }
}
