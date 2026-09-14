<?php

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\VnPayGateway;
use App\Support\CommerceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

it('rejects invalid signatures on the fake gateway', function () {
    $gateway = new FakePaymentGateway;
    $payload = [
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => 'ORD-1',
        'vnp_SecureHash' => 'bad',
    ];

    $gateway->verify($payload);
})->throws(CommerceException::class);

it('accepts a valid fake gateway signature for success and failure codes', function () {
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret']);
    $gateway = new FakePaymentGateway;
    $base = [
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => 'ORD-OK',
        'vnp_TransactionNo' => 'TXN1',
    ];
    $base['vnp_SecureHash'] = $gateway->hash($base);
    $ok = $gateway->verify($base);
    expect($ok->ok)->toBeTrue()->and($ok->providerTxnId)->toBe('TXN1')->and($ok->orderRef)->toBe('ORD-OK');

    $fail = [
        'vnp_ResponseCode' => '24',
        'vnp_TxnRef' => 'ORD-FAIL',
    ];
    $fail['vnp_SecureHash'] = $gateway->hash($fail);
    $result = $gateway->verify($fail);
    expect($result->ok)->toBeFalse()->and($result->responseCode)->toBe('24');
});

it('hashes and verifies with the VNPay gateway using sha512 hmac', function () {
    config(['commerce.vnpay.hash_secret' => 'secret']);
    $gateway = new VnPayGateway;
    $payload = [
        'vnp_Amount' => '10000000',
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => 'ORD-VNP',
        'vnp_TransactionNo' => '99',
    ];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);
    $result = $gateway->verify($payload);
    expect($result->ok)->toBeTrue()->and($result->providerTxnId)->toBe('99');
});

it('refunds through VnPayGateway with hmac and http fake', function () {
    Http::fake(['*' => Http::response(['vnp_ResponseCode' => '00', 'vnp_TransactionNo' => 'R1'], 200)]);
    config([
        'commerce.vnpay.hash_secret' => 'secret',
        'commerce.vnpay.tmn_code' => 'TMN',
        'commerce.vnpay.refund_url' => 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction',
    ]);
    $order = Order::factory()->create(['number' => 'ORD-REF', 'grand_total' => 100000]);
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'provider' => 'vnpay',
        'provider_txn_id' => '99',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 100000,
        'status' => 'succeeded',
        'payload' => ['vnp_TransactionDate' => '20260914120000'],
    ]);
    $refund = Refund::factory()->create([
        'payment_transaction_id' => $txn->id,
        'amount' => 100000,
        'idempotency_key' => (string) Str::uuid(),
        'reason' => 'test',
    ]);
    $result = (new VnPayGateway)->refund($txn->load('order'), $refund);
    expect($result->ok)->toBeTrue()->and($result->providerRefundId)->toBe('R1');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'merchant_webapi'));
});
