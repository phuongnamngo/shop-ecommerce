<?php

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function customerStaffAdmin(): AdminUser
{
    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    return $admin;
}

it('lists and shows customers for staff with customers.view without leaking secrets', function () {
    $match = Customer::factory()->create([
        'name' => 'Alice Nguyen',
        'email' => 'alice.unique@example.com',
        'phone' => '0912345678',
        'status' => Customer::STATUS_ACTIVE,
    ]);
    Customer::factory()->create([
        'name' => 'Bob Inactive',
        'email' => 'bob.unique@example.com',
        'status' => Customer::STATUS_INACTIVE,
    ]);

    CustomerAddress::query()->create([
        'customer_id' => $match->id,
        'label' => 'Home',
        'recipient_name' => 'Alice Nguyen',
        'phone' => '0912345678',
        'province_code' => 'P',
        'district_code' => 'D',
        'ward_code' => 'W',
        'address_line' => 'Road 1',
        'postal_code' => '700000',
        'is_default' => true,
    ]);

    $this->actingAs(customerStaffAdmin(), 'admin');

    $list = $this->getJson('/api/v1/admin/customers?status=active&q=alice.unique')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    $ids = collect($list->json('data'))->pluck('id')->all();
    expect($ids)->toContain($match->id)->not->toContain(
        Customer::query()->where('email', 'bob.unique@example.com')->value('id'),
    );

    foreach ($list->json('data') as $row) {
        expect($row)->toHaveKeys([
            'id', 'code', 'name', 'email', 'phone', 'status',
            'created_at', 'last_login_at', 'email_verified_at',
        ])->not->toHaveKeys(['password', 'remember_token', 'tokens']);
    }

    $show = $this->getJson('/api/v1/admin/customers/'.$match->id)
        ->assertOk()
        ->assertJsonPath('data.id', $match->id)
        ->assertJsonPath('data.email', 'alice.unique@example.com')
        ->assertJsonPath('data.addresses.0.address_line', 'Road 1')
        ->assertJsonPath('data.addresses.0.is_default', true);

    expect($show->json('data'))->not->toHaveKeys(['password', 'remember_token', 'tokens']);
});

it('forbids staff without customers.view', function () {
    $staffRole = Role::findByName('staff', 'admin');
    $staffRole->revokePermissionTo('customers.view');

    $admin = AdminUser::factory()->create();
    $admin->assignRole('staff');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/customers')
        ->assertForbidden();
});

it('returns 404 for missing customer', function () {
    $this->actingAs(customerStaffAdmin(), 'admin')
        ->getJson('/api/v1/admin/customers/999999')
        ->assertNotFound();
});

it('returns 404 for soft-deleted customer', function () {
    $customer = Customer::factory()->create();
    $customer->delete();

    $this->actingAs(customerStaffAdmin(), 'admin')
        ->getJson('/api/v1/admin/customers/'.$customer->id)
        ->assertNotFound();
});
