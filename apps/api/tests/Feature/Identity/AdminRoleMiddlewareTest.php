<?php

use App\Models\AdminUser;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows staff admin to access me', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin');

    $this->getJson('/api/v1/admin/me')
        ->assertOk()
        ->assertJsonPath('data.email', $admin->email);
});

it('blocks banned admin from me', function () {
    $admin = AdminUser::factory()->banned()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin');

    $this->getJson('/api/v1/admin/me')
        ->assertForbidden();
});

it('forbids admin without backoffice role on me', function () {
    $admin = AdminUser::factory()->create();

    $this->actingAs($admin, 'admin');

    $this->getJson('/api/v1/admin/me')
        ->assertForbidden();
});

it('forbids staff without customers.view on customers-check', function () {
    $staffRole = Role::findByName('staff', 'admin');
    $staffRole->revokePermissionTo('customers.view');

    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin');

    $this->getJson('/api/v1/admin/customers-check')
        ->assertForbidden();
});

it('allows staff with customers.view on customers-check', function () {
    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin');

    $this->getJson('/api/v1/admin/customers-check')
        ->assertOk();
});
