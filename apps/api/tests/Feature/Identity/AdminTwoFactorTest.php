<?php

use App\Models\AdminTwoFactorRecoveryCode;
use App\Models\AdminUser;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use PragmaRX\Google2FA\Google2FA;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('keeps a session when two-factor is not confirmed', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'boss@example.com',
        'password' => 'password',
    ]);
    $admin->assignRole('super_admin');

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])->assertOk()->assertJsonPath('data.email', 'boss@example.com');

    $this->getJson('/api/v1/admin/me')->assertOk();

    $admin->forceFill(['two_factor_confirmed_at' => null])->save();
    expect($admin->fresh()->two_factor_confirmed_at)->toBeNull();
});

it('requires a challenge when two-factor is confirmed and accepts totp or one recovery code', function () {
    $google2fa = new Google2FA;
    $secret = $google2fa->generateSecretKey();
    $admin = AdminUser::factory()->create([
        'email' => 'boss@example.com',
        'password' => 'password',
    ]);
    $admin->forceFill([
        'two_factor_secret' => $secret,
        'two_factor_confirmed_at' => now(),
    ])->save();
    $admin->assignRole('super_admin');
    AdminTwoFactorRecoveryCode::query()->create([
        'admin_user_id' => $admin->id,
        'code_hash' => hash('sha256', 'abcd1234ef'),
    ]);

    $login = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])->assertUnauthorized()
        ->assertJsonPath('errors.0.code', ErrorCode::AUTH_TWO_FACTOR_REQUIRED);

    $token = $login->json('meta.two_factor_token');
    expect($token)->toBeString()->toHaveLength(64);
    $this->getJson('/api/v1/admin/me')->assertUnauthorized();

    $this->postJson('/api/v1/admin/auth/two-factor/challenge', [
        'two_factor_token' => $token,
        'code' => '000000',
    ])->assertUnprocessable()->assertJsonPath('errors.0.code', ErrorCode::AUTH_TWO_FACTOR_INVALID);
    $this->getJson('/api/v1/admin/me')->assertUnauthorized();

    $this->postJson('/api/v1/admin/auth/two-factor/challenge', [
        'two_factor_token' => $token,
        'code' => $google2fa->getCurrentOtp($secret),
    ])->assertOk()->assertJsonPath('data.email', 'boss@example.com')
        ->assertJsonMissingPath('data.two_factor_secret');

    $this->getJson('/api/v1/admin/me')->assertOk()->assertJsonMissingPath('data.two_factor_secret');
    $this->postJson('/api/v1/admin/auth/logout')->assertOk();

    $this->postJson('/api/v1/admin/auth/two-factor/challenge', [
        'two_factor_token' => str_repeat('a', 64),
        'code' => $google2fa->getCurrentOtp($secret),
    ])->assertUnauthorized()
        ->assertJsonPath('errors.0.code', ErrorCode::AUTH_TWO_FACTOR_REQUIRED)
        ->assertJsonMissingPath('meta.two_factor_token');

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'wrong',
    ])->assertUnauthorized()->assertJsonPath('errors.0.code', ErrorCode::AUTH_INVALID_CREDENTIALS);

    $second = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])->assertUnauthorized();

    $this->postJson('/api/v1/admin/auth/two-factor/challenge', [
        'two_factor_token' => $second->json('meta.two_factor_token'),
        'code' => 'abcd1234ef',
    ])->assertOk();

    expect(AdminTwoFactorRecoveryCode::query()->where('admin_user_id', $admin->id)->count())->toBe(0);

    $this->postJson('/api/v1/admin/auth/logout')->assertOk();
    $third = $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])->assertUnauthorized();
    $this->postJson('/api/v1/admin/auth/two-factor/challenge', [
        'two_factor_token' => $third->json('meta.two_factor_token'),
        'code' => 'abcd1234ef',
    ])->assertUnprocessable();
});

it('confirms totp setup once and returns recovery codes a single time', function () {
    $google2fa = new Google2FA;
    $admin = AdminUser::factory()->create([
        'email' => 'boss@example.com',
        'password' => 'password',
    ]);
    $admin->assignRole('super_admin');

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])->assertOk();

    $this->postJson('/api/v1/admin/auth/two-factor/confirm', [
        'code' => '000000',
    ])->assertUnprocessable();

    $setup = $this->postJson('/api/v1/admin/auth/two-factor/setup')
        ->assertOk();
    $secret = $setup->json('data.secret');
    expect($secret)->toBeString()->not->toBeEmpty()
        ->and($setup->json('data.otpauth_uri'))->toStartWith('otpauth://totp/')->toContain('issuer=Watch');
    expect($admin->fresh()->two_factor_confirmed_at)->toBeNull();

    $confirm = $this->postJson('/api/v1/admin/auth/two-factor/confirm', [
        'code' => $google2fa->getCurrentOtp($secret),
    ])->assertOk();
    $codes = $confirm->json('data.recovery_codes');
    expect($codes)->toHaveCount(8);
    foreach ($codes as $code) {
        expect($code)->toHaveLength(10);
        expect(AdminTwoFactorRecoveryCode::query()->where('code_hash', $code)->exists())->toBeFalse();
    }
    expect($admin->fresh()->two_factor_confirmed_at)->not->toBeNull();

    $this->postJson('/api/v1/admin/auth/two-factor/setup')->assertStatus(409);
});
