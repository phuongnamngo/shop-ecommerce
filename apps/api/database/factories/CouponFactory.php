<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'discount_id' => null,
            'max_uses' => null,
            'max_uses_per_customer' => null,
            'used_count' => 0,
            'starts_at' => null,
            'ends_at' => null,
            'status' => 'active',
        ];
    }

    public function forDiscount(Discount $discount): static
    {
        return $this->state(fn () => ['discount_id' => $discount->id]);
    }
}
