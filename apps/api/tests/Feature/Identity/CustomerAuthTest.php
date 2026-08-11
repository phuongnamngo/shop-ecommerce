<?php

use App\Models\Customer;
use App\Support\ErrorCode;

it('registers customer with 201 and session', function () {
    $this->postJson('/api/v1/customer/auth/register', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'ada@example.com')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonMissing(['errors']);

    $this->getJson('/api/v1/customer/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'ada@example.com')
        ->assertJsonPath('data.phone', null);
});

it('logs in and logs out customer', function () {
    Customer::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/customer/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonPath('data.email', 'ada@example.com');

    $this->postJson('/api/v1/customer/auth/logout')
        ->assertOk()
        ->assertJsonPath('data', null);

    $this->getJson('/api/v1/customer/me')->assertUnauthorized();
});

it('rejects invalid credentials with AUTH_INVALID_CREDENTIALS', function () {
    Customer::factory()->create(['email' => 'ada@example.com']);

    $this->postJson('/api/v1/customer/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong',
    ])
        ->assertUnauthorized()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_INVALID_CREDENTIALS]);
});

it('rejects banned customer after valid password with AUTH_ACCOUNT_BANNED', function () {
    Customer::factory()->banned()->create([
        'email' => 'bad@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/customer/auth/login', [
        'email' => 'bad@example.com',
        'password' => 'password123',
    ])
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_ACCOUNT_BANNED]);

    $this->assertGuest('customer');
});

it('rejects inactive customer with AUTH_ACCOUNT_INACTIVE', function () {
    Customer::factory()->create([
        'email' => 'idle@example.com',
        'password' => 'password123',
        'status' => Customer::STATUS_INACTIVE,
    ]);

    $this->postJson('/api/v1/customer/auth/login', [
        'email' => 'idle@example.com',
        'password' => 'password123',
    ])
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_ACCOUNT_INACTIVE]);
});

it('throttles customer login', function () {
    Customer::factory()->create(['email' => 'throttle@example.com', 'password' => 'password123']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/customer/auth/login', [
            'email' => 'throttle@example.com',
            'password' => 'wrong',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/v1/customer/auth/login', [
        'email' => 'throttle@example.com',
        'password' => 'wrong',
    ])
        ->assertStatus(429)
        ->assertJsonFragment(['code' => ErrorCode::AUTH_THROTTLED]);
});
