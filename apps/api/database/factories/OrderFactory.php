<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = 100000;

        return [
            'number' => 'ORD-'.Str::upper((string) Str::ulid()),
            'customer_id' => null,
            'status' => 'pending',
            'currency' => 'VND',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'grand_total' => $subtotal,
            'shipping_address_snapshot' => null,
            'billing_address_snapshot' => null,
            'shipping_method_id' => null,
            'coupon_id' => null,
        ];
    }
}
