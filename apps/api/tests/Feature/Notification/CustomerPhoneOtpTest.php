<?php

use App\Contracts\SmsGateway;
use App\Models\Customer;
use App\Services\Sms\FakeSmsGateway;
use App\Services\Sms\UnavailableSmsGateway;
use App\Support\ErrorCode;

it('rejects guest phone otp send', function () {
    $this->postJson('/api/v1/customer/phone/otp')->assertUnauthorized();
});

it('sends otp to the saved phone without leaking the code', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $response = $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertOk();
    $gateway = app(SmsGateway::class);
    expect($gateway)->toBeInstanceOf(FakeSmsGateway::class)
        ->and($gateway->lastTo)->toBe('0900000001')
        ->and($gateway->lastOtp)->toHaveLength(6)
        ->and($response->json('data.expires_at'))->toBeString()
        ->and($response->getContent())->not->toContain($gateway->lastOtp);
});

it('rejects send when phone is missing', function () {
    $customer = Customer::factory()->create(['phone' => null]);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_REQUIRED]);
});

it('rejects send when phone is already verified', function () {
    $customer = Customer::factory()->create([
        'phone' => '0900000001',
        'phone_verified_at' => now(),
    ]);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_ALREADY_VERIFIED]);
});

it('returns SMS_UNAVAILABLE when the gateway cannot send', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->app->instance(SmsGateway::class, new UnavailableSmsGateway);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertStatus(503)
        ->assertJsonFragment(['code' => ErrorCode::SMS_UNAVAILABLE]);
});

it('throttles phone otp send', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->actingAs($customer, 'customer');
    for ($i = 0; $i < 3; $i++) {
        $this->postJson('/api/v1/customer/phone/otp')->assertOk();
    }
    $this->postJson('/api/v1/customer/phone/otp')
        ->assertStatus(429)
        ->assertJsonFragment(['code' => ErrorCode::AUTH_THROTTLED]);
});

it('verifies a valid otp', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertOk();
    $code = app(SmsGateway::class)->lastOtp;
    $this->postJson('/api/v1/customer/phone/otp/verify', ['code' => $code])
        ->assertOk()
        ->assertJsonPath('data.phone', '0900000001');
    expect($this->getJson('/api/v1/customer/me')->json('data.phone_verified_at'))->not->toBeNull()
        ->and($customer->fresh()->phone_verified_at)->not->toBeNull();
});

it('rejects an invalid otp code', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertOk();
    $code = app(SmsGateway::class)->lastOtp;
    $wrong = $code === '000000' ? '111111' : '000000';
    $this->postJson('/api/v1/customer/phone/otp/verify', ['code' => $wrong])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_OTP_INVALID]);
});

it('rejects verify when no otp was sent', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp/verify', ['code' => '123456'])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_OTP_EXPIRED]);
});

it('rejects otp after the phone number changes', function () {
    $customer = Customer::factory()->create(['name' => 'A', 'phone' => '0900000001']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertOk();
    $code = app(SmsGateway::class)->lastOtp;
    $this->patchJson('/api/v1/customer/me', ['name' => 'A', 'phone' => '0900000002'])->assertOk();
    $this->postJson('/api/v1/customer/phone/otp/verify', ['code' => $code])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_OTP_EXPIRED]);
    expect($customer->fresh()->phone_verified_at)->toBeNull();
});

it('rejects verify when already verified', function () {
    $customer = Customer::factory()->create([
        'phone' => '0900000001',
        'phone_verified_at' => now(),
    ]);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp/verify', ['code' => '123456'])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_ALREADY_VERIFIED]);
});

it('rejects a short otp code', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp/verify', ['code' => '12'])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::VALIDATION_FAILED]);
});

it('invalidates the previous otp when a new one is sent', function () {
    $customer = Customer::factory()->create(['phone' => '0900000001']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/phone/otp')
        ->assertOk();
    $first = app(SmsGateway::class)->lastOtp;
    $this->postJson('/api/v1/customer/phone/otp')->assertOk();
    $this->postJson('/api/v1/customer/phone/otp/verify', ['code' => $first])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PHONE_OTP_INVALID]);
});

