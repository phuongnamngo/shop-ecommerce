<?php

use App\Models\Order;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('returns empty metrics as zero for staff with orders.view', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.today.order_count', 0)
        ->assertJsonPath('data.today.revenue', '0.00')
        ->assertJsonPath('data.month.order_count', 0)
        ->assertJsonPath('data.month.revenue', '0.00')
        ->assertJsonPath('data.currency', 'VND');
});

it('counts paid-plus today into both today and month', function () {
    Order::factory()->create([
        'status' => 'paid',
        'grand_total' => 100000,
        'currency' => 'VND',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.today.order_count', 1)
        ->assertJsonPath('data.today.revenue', '100000.00')
        ->assertJsonPath('data.month.order_count', 1)
        ->assertJsonPath('data.month.revenue', '100000.00');
});

it('excludes pending cancelled soft-deleted and outside window', function () {
    Order::factory()->create(['status' => 'pending', 'grand_total' => 50000, 'created_at' => now()]);
    Order::factory()->create(['status' => 'cancelled', 'grand_total' => 50000, 'created_at' => now()]);
    $deleted = Order::factory()->create(['status' => 'paid', 'grand_total' => 50000, 'created_at' => now()]);
    $deleted->delete();
    Order::factory()->create([
        'status' => 'paid',
        'grand_total' => 50000,
        'created_at' => now()->subMonths(2),
        'updated_at' => now()->subMonths(2),
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertOk()
        ->assertJsonPath('data.today.order_count', 0)
        ->assertJsonPath('data.today.revenue', '0.00')
        ->assertJsonPath('data.month.order_count', 0)
        ->assertJsonPath('data.month.revenue', '0.00');
});

it('forbids staff without orders.view', function () {
    $admin = catalogAdmin('staff');
    Permission::findByName('orders.view', 'admin')->removeRole('staff');
    $admin->forgetCachedPermissions();

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/dashboard/metrics')
        ->assertForbidden();
});
