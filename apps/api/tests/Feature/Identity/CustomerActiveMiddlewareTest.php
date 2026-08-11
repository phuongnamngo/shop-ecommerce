<?php

use App\Models\Customer;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('allows active customer to access me', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    $this->getJson('/api/v1/customer/me')
        ->assertOk()
        ->assertJsonPath('data.email', $customer->email)
        ->assertJsonMissing(['errors']);
});

it('blocks banned customer from me', function () {
    $customer = Customer::factory()->banned()->create();

    $this->actingAs($customer, 'customer');

    $this->getJson('/api/v1/customer/me')
        ->assertForbidden()
        ->assertJsonFragment(['code' => ErrorCode::AUTH_ACCOUNT_BANNED]);
});
