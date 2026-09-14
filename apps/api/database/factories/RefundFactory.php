<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    protected $model = Refund::class;

    public function definition(): array
    {
        return [
            'payment_transaction_id' => function () {
                $order = Order::factory()->create(['grand_total' => 100000]);

                return PaymentTransaction::query()->create([
                    'order_id' => $order->id,
                    'payment_method_id' => null,
                    'provider' => 'cod',
                    'idempotency_key' => (string) Str::uuid(),
                    'amount' => $order->grand_total,
                    'status' => 'succeeded',
                ])->id;
            },
            'amount' => '100000.00',
            'status' => Refund::STATUS_PENDING,
            'reason' => fake()->sentence(),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
