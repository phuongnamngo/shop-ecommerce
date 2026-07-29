<?php

use App\Models\Customer;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows active customer to access me', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    $this->getJson('/api/v1/customer/me')
        ->assertOk()
        ->assertJsonPath('data.email', $customer->email);
});

it('blocks banned customer from me', function () {
    $customer = Customer::factory()->banned()->create();

    $this->actingAs($customer, 'customer');

    $this->getJson('/api/v1/customer/me')
        ->assertForbidden();
});
