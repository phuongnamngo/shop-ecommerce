<?php

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\Payment\FakePaymentGateway;
use Illuminate\Support\Str;

it('settles VNPay IPN once and ignores duplicates', function () {
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret']);
    $method = PaymentMethod::query()->create(['code' => 'vnpay', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create(['status' => 'pending', 'number' => 'ORD-IPN1', 'grand_total' => 100000]);
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'vnpay',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 100000,
        'status' => 'pending',
    ]);
    $gateway = new FakePaymentGateway;
    $payload = [
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => 'ORD-IPN1',
        'vnp_TransactionNo' => 'VN123',
    ];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))
        ->assertOk()
        ->assertSee('Confirm Success');
    expect($order->refresh()->status)->toBe('paid')
        ->and($txn->refresh()->status)->toBe('succeeded')
        ->and($txn->provider_txn_id)->toBe('VN123');
    $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => 'paid', 'changed_by_admin_id' => null]);

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))->assertOk();
    expect($order->refresh()->status)->toBe('paid');
    $this->assertDatabaseCount('webhook_events', 1);
});

it('marks payment failed on non-success VNPay response without paying the order', function () {
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret']);
    $method = PaymentMethod::query()->create(['code' => 'vnpay', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create(['status' => 'pending', 'number' => 'ORD-FAIL1', 'grand_total' => 50000]);
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'vnpay',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 50000,
        'status' => 'pending',
    ]);
    $gateway = new FakePaymentGateway;
    $payload = ['vnp_ResponseCode' => '24', 'vnp_TxnRef' => 'ORD-FAIL1'];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))->assertOk();
    expect($order->refresh()->status)->toBe('pending')->and($txn->refresh()->status)->toBe('failed');
});

it('does not resurrect a cancelled order from a late IPN', function () {
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret']);
    $method = PaymentMethod::query()->create(['code' => 'vnpay', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create(['status' => 'cancelled', 'number' => 'ORD-CANCEL1', 'grand_total' => 10000]);
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'vnpay',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 10000,
        'status' => 'pending',
    ]);
    $gateway = new FakePaymentGateway;
    $payload = ['vnp_ResponseCode' => '00', 'vnp_TxnRef' => 'ORD-CANCEL1', 'vnp_TransactionNo' => 'X'];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))->assertOk();
    expect($order->refresh()->status)->toBe('cancelled')->and($txn->refresh()->status)->toBe('pending');
});
