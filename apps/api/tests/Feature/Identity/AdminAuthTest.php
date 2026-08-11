<?php

use App\Models\AdminUser;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('logs in admin and returns roles on me', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'boss@example.com',
        'password' => 'password',
    ]);
    $admin->assignRole('super_admin');

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.email', 'boss@example.com')
        ->assertJsonFragment(['super_admin']);

    $this->getJson('/api/v1/admin/me')
        ->assertOk()
        ->assertJsonPath('data.email', 'boss@example.com')
        ->assertJsonFragment(['super_admin']);
});

it('logs out admin', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'boss@example.com',
        'password' => 'password',
    ]);
    $admin->assignRole('staff');

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'password',
    ])->assertOk();

    $this->postJson('/api/v1/admin/auth/logout')
        ->assertOk()
        ->assertJsonPath('data', null);

    $this->getJson('/api/v1/admin/me')->assertUnauthorized();
});

it('does not expose admin register route', function () {
    $this->postJson('/api/v1/admin/auth/register', [])->assertNotFound();
});

it('rejects invalid admin credentials', function () {
    AdminUser::factory()->create(['email' => 'boss@example.com', 'password' => 'password']);

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'boss@example.com',
        'password' => 'wrong',
    ])
        ->assertUnauthorized()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_INVALID_CREDENTIALS]);
});

it('rejects banned admin after valid password', function () {
    $admin = AdminUser::factory()->create([
        'email' => 'banned@example.com',
        'password' => 'password',
        'status' => AdminUser::STATUS_BANNED,
    ]);
    $admin->assignRole('staff');

    $this->postJson('/api/v1/admin/auth/login', [
        'email' => 'banned@example.com',
        'password' => 'password',
    ])
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_ACCOUNT_BANNED]);
});
