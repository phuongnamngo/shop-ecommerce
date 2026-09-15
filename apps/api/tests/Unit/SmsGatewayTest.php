<?php

use App\Services\Sms\FakeSmsGateway;
use App\Services\Sms\UnavailableSmsGateway;
use App\Support\CommerceException;
use App\Support\ErrorCode;

it('records last destination body and six-digit otp', function () {
    $fake = new FakeSmsGateway;
    $fake->send('0900000001', 'Ma xac minh Watch: 123456. Hieu luc 5 phut.');
    expect($fake->lastTo)->toBe('0900000001')
        ->and($fake->lastOtp)->toBe('123456');
});

it('throws SMS_UNAVAILABLE on send', function () {
    try {
        (new UnavailableSmsGateway)->send('0900000001', 'x');
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::SMS_UNAVAILABLE)
            ->and($e->status)->toBe(503);
    }
});
