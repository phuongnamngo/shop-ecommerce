<?php

use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\VnPayGateway;
use App\Support\CommerceException;

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
