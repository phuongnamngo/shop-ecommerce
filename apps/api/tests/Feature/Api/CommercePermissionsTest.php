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

it('seeds promotion discount and coupon permissions for admin roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::findByName('admin', 'admin');
    $staff = Role::findByName('staff', 'admin');

    expect($admin->hasPermissionTo('promotions.discounts.view', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('promotions.discounts.manage', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('promotions.coupons.view', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('promotions.coupons.manage', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('promotions.discounts.view', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('promotions.coupons.view', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('promotions.discounts.manage', 'admin'))->toBeFalse()
        ->and($staff->hasPermissionTo('promotions.coupons.manage', 'admin'))->toBeFalse();
});

it('seeds promotion flash sale permissions for admin roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::findByName('admin', 'admin');
    $staff = Role::findByName('staff', 'admin');

    expect($admin->hasPermissionTo('promotions.flash_sales.view', 'admin'))->toBeTrue()
        ->and($admin->hasPermissionTo('promotions.flash_sales.manage', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('promotions.flash_sales.view', 'admin'))->toBeTrue()
        ->and($staff->hasPermissionTo('promotions.flash_sales.manage', 'admin'))->toBeFalse();
});
