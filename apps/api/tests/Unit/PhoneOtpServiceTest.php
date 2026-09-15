<?php

use App\Contracts\SmsGateway;
use App\Models\Customer;
use App\Services\Identity\PhoneOtpService;
use App\Services\Sms\FakeSmsGateway;
use App\Services\Sms\UnavailableSmsGateway;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('sends a six digit code to the persisted phone', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $fake = new FakeSmsGateway;
    $this->app->instance(SmsGateway::class, $fake);
    $out = $this->app->make(PhoneOtpService::class)->send($customer);
    expect($fake->lastTo)->toBe('0900000001')
        ->and($fake->lastOtp)->toHaveLength(6)
        ->and($out['expires_at'])->not->toBeEmpty();
});

it('rejects send without phone', function () {
    $customer = Customer::factory()->create(['phone' => null]);
    $this->app->instance(SmsGateway::class, new FakeSmsGateway);
    try {
        $this->app->make(PhoneOtpService::class)->send($customer);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::PHONE_REQUIRED)->and($e->status)->toBe(422);
    }
});

it('rejects send when already verified', function () {
    $customer = Customer::factory()->create([
        'phone' => '0900000001',
        'phone_verified_at' => now(),
    ]);
    $this->app->instance(SmsGateway::class, new FakeSmsGateway);
    try {
        $this->app->make(PhoneOtpService::class)->send($customer);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::PHONE_ALREADY_VERIFIED);
    }
});

it('verifies a valid otp and sets phone_verified_at', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $fake = new FakeSmsGateway;
    $this->app->instance(SmsGateway::class, $fake);
    $service = $this->app->make(PhoneOtpService::class);
    $service->send($customer);
    $verified = $service->verify($customer, (string) $fake->lastOtp);
    expect($verified->phone_verified_at)->not->toBeNull();
});

it('rejects an invalid otp', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $fake = new FakeSmsGateway;
    $this->app->instance(SmsGateway::class, $fake);
    $service = $this->app->make(PhoneOtpService::class);
    $service->send($customer);
    $wrong = $fake->lastOtp === '000000' ? '111111' : '000000';
    try {
        $service->verify($customer, $wrong);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::PHONE_OTP_INVALID);
    }
});

it('rejects an expired otp', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $fake = new FakeSmsGateway;
    $this->app->instance(SmsGateway::class, $fake);
    $service = $this->app->make(PhoneOtpService::class);
    $service->send($customer);
    $this->travel(6)->minutes();
    try {
        $service->verify($customer, (string) $fake->lastOtp);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::PHONE_OTP_EXPIRED);
    }
});

it('rejects otp after the saved phone changes', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $fake = new FakeSmsGateway;
    $this->app->instance(SmsGateway::class, $fake);
    $service = $this->app->make(PhoneOtpService::class);
    $service->send($customer);
    $code = (string) $fake->lastOtp;
    $customer->forceFill(['phone' => '0900000002'])->save();
    try {
        $service->verify($customer->fresh(), $code);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::PHONE_OTP_EXPIRED);
    }
});

it('does not cache otp when sms is unavailable', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->app->instance(SmsGateway::class, new UnavailableSmsGateway);
    $service = $this->app->make(PhoneOtpService::class);
    try {
        $service->send($customer);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::SMS_UNAVAILABLE);
    }
    $this->app->instance(SmsGateway::class, new FakeSmsGateway);
    try {
        $this->app->make(PhoneOtpService::class)->verify($customer, '123456');
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::PHONE_OTP_EXPIRED);
    }
});
