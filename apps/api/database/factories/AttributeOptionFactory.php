<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeOption>
 */
class AttributeOptionFactory extends Factory
{
    protected $model = AttributeOption::class;

    public function definition(): array
    {
        return [
            'attribute_id' => Attribute::factory(),
            'code' => (string) Str::ulid(),
            'label' => fake()->unique()->word(),
            'position' => 0,
        ];
    }
}
