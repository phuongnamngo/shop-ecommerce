<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'rating' => fake()->numberBetween(1, 5),
            'body' => fake()->optional()->sentence(),
            'status' => 'pending',
        ];
    }
}
