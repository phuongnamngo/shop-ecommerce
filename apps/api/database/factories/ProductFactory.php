<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'code' => (string) Str::ulid(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'status' => Product::STATUS_DRAFT,
            'published_at' => null,
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            if ($product->variants()->exists()) {
                return;
            }

            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'is_default' => true,
            ]);
        });
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Product::STATUS_ACTIVE,
            'published_at' => now()->subMinute(),
        ]);
    }
}
