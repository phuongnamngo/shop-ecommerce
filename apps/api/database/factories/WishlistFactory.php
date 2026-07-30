<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
{
    protected $model = Wishlist::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => 'Default',
        ];
    }
}
