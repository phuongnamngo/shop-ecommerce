<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

it('seeds inventory and order permissions for admin roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::findByName('admin', 'admin');
    $staff = Role::findByName('staff', 'admin');

    expect($admin->hasPermissionTo('inventory.view', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('inventory.manage', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('orders.view', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('orders.manage', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('inventory.view', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('orders.view', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('inventory.manage', 'admin'))->toBeFalse()
        ->and($staff->hasPermissionTo('orders.manage', 'admin'))->toBeFalse();
});
