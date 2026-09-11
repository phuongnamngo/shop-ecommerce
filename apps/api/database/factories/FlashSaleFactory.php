<?php

namespace Database\Factories;

use App\Models\FlashSale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FlashSale>
 */
class FlashSaleFactory extends Factory
{
    protected $model = FlashSale::class;

    public function definition(): array
    {
        return [
            'code' => (string) Str::ulid(),
            'name' => fake()->words(3, true),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'status' => FlashSale::STATUS_ACTIVE,
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => FlashSale::STATUS_SCHEDULED,
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addDays(2),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => FlashSale::STATUS_ENDED,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subHour(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => FlashSale::STATUS_CANCELLED,
        ]);
    }
}
