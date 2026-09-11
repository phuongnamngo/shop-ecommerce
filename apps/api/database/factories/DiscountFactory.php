<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        $type = fake()->randomElement([Discount::TYPE_FIXED, Discount::TYPE_PERCENTAGE]);

        return [
            'code' => (string) Str::ulid(),
            'name' => fake()->words(3, true),
            'type' => $type,
            'value' => $type === Discount::TYPE_PERCENTAGE
                ? fake()->randomElement([5, 10, 15, 20])
                : fake()->randomElement([10000, 20000, 50000]),
            'starts_at' => null,
            'ends_at' => null,
            'status' => Discount::STATUS_ACTIVE,
        ];
    }

    public function percentage(float $value = 10): static
    {
        return $this->state(fn () => [
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => $value,
        ]);
    }

    public function fixed(float $value = 10000): static
    {
        return $this->state(fn () => [
            'type' => Discount::TYPE_FIXED,
            'value' => $value,
        ]);
    }
}
