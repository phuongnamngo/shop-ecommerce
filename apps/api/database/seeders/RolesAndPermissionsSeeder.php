<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guard = 'admin';

        $permissions = [
            'admin_users.view',
            'admin_users.manage',
            'customers.view',
            'customers.manage',
            'catalog.brands.view',
            'catalog.brands.manage',
            'catalog.categories.view',
            'catalog.categories.manage',
            'catalog.products.view',
            'catalog.products.manage',
            'catalog.attributes.view',
            'catalog.attributes.manage',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $superAdmin = Role::findOrCreate('super_admin', $guard);
        $admin = Role::findOrCreate('admin', $guard);
        $staff = Role::findOrCreate('staff', $guard);

        $superAdmin->syncPermissions(Permission::where('guard_name', $guard)->get());

        $admin->syncPermissions([
            'admin_users.view',
            'customers.view',
            'customers.manage',
            'catalog.brands.view',
            'catalog.brands.manage',
            'catalog.categories.view',
            'catalog.categories.manage',
            'catalog.products.view',
            'catalog.products.manage',
            'catalog.attributes.view',
            'catalog.attributes.manage',
        ]);

        $staff->syncPermissions([
            'customers.view',
            'catalog.brands.view',
            'catalog.categories.view',
            'catalog.products.view',
            'catalog.attributes.view',
        ]);
    }
}
