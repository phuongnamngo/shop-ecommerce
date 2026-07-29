<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'code' => (string) Str::ulid(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'position' => 0,
        ];
    }
}
