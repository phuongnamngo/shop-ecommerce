<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('stores cart item unit_price snapshot', function () {
    $variant = ProductVariant::factory()->create(['price' => 150000]);
    $cart = Cart::factory()->create();
    $item = CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_variant_id' => $variant->id,
        'qty' => 2,
        'unit_price' => 150000,
    ]);

    expect((float) $item->fresh()->unit_price)->toBe(150000.0);
});

it('enforces unique order number', function () {
    Order::factory()->create(['number' => 'ORD-TEST-1']);

    expect(fn () => Order::factory()->create(['number' => 'ORD-TEST-1']))
        ->toThrow(QueryException::class);
});

it('enforces unique payment idempotency_key', function () {
    $order = Order::factory()->create();
    $key = (string) Str::uuid();

    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => null,
        'provider' => 'vnpay',
        'provider_txn_id' => null,
        'idempotency_key' => $key,
        'amount' => 100000,
        'status' => 'pending',
        'payload' => [],
    ]);

    expect(fn () => PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => null,
        'provider' => 'vnpay',
        'provider_txn_id' => null,
        'idempotency_key' => $key,
        'amount' => 100000,
        'status' => 'pending',
        'payload' => [],
    ]))->toThrow(QueryException::class);
});
