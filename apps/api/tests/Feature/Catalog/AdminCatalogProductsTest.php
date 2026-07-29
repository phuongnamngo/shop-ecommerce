<?php

use App\Models\AdminUser;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('forbids admin without catalog.products.view', function () {
    $staffRole = Role::findByName('staff', 'admin');
    $staffRole->revokePermissionTo('catalog.products.view');

    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/catalog/products')
        ->assertForbidden();
});

it('allows staff with catalog.products.view', function () {
    Product::factory()->create();
    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/catalog/products')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'slug', 'status']]]);
});
