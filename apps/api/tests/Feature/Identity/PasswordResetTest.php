<?php

use App\Models\AdminUser;
use App\Models\Customer;
use App\Notifications\AdminResetPassword;
use App\Notifications\CustomerResetPassword;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('sends customer reset notification with storefront path', function () {
    Notification::fake();
    $customer = Customer::factory()->create();

    $this->postJson('/api/v1/customer/auth/forgot-password', [
        'email' => $customer->email,
    ])->assertOk();

    Notification::assertSentTo($customer, CustomerResetPassword::class, function (CustomerResetPassword $notification) use ($customer) {
        $url = $notification->resetUrl($customer);

        return str_contains($url, '/reset-password?')
            && ! str_contains($url, '/admin/reset-password');
    });
});

it('sends admin reset notification with admin path', function () {
    Notification::fake();
    $admin = AdminUser::factory()->create();

    $this->postJson('/api/v1/admin/auth/forgot-password', [
        'email' => $admin->email,
    ])->assertOk();

    Notification::assertSentTo($admin, AdminResetPassword::class, function (AdminResetPassword $notification) use ($admin) {
        return str_contains($notification->resetUrl($admin), '/admin/reset-password?');
    });
});

it('returns generic success when customer email missing', function () {
    Notification::fake();

    $this->postJson('/api/v1/customer/auth/forgot-password', [
        'email' => 'missing@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('data.message', fn ($message) => is_string($message));

    Notification::assertNothingSent();
});

it('returns AUTH_RESET_TOKEN_INVALID for bad customer token', function () {
    $customer = Customer::factory()->create();

    $this->postJson('/api/v1/customer/auth/reset-password', [
        'email' => $customer->email,
        'token' => 'invalid',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_RESET_TOKEN_INVALID]);
});

it('resets customer password with valid token', function () {
    Notification::fake();
    $customer = Customer::factory()->create(['email' => 'reset@example.com']);

    $token = Password::broker('customers')->createToken($customer);

    $this->postJson('/api/v1/customer/auth/reset-password', [
        'email' => $customer->email,
        'token' => $token,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertOk();

    $this->postJson('/api/v1/customer/auth/login', [
        'email' => 'reset@example.com',
        'password' => 'new-password-123',
    ])->assertOk();
});
