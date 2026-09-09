<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;

function seedGeoTree(): array
{
    $province = GeoProvince::query()->create(['code' => 'P1', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'D1', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'W1', 'name' => 'Ward']);

    return ['province_code' => 'P1', 'district_code' => 'D1', 'ward_code' => 'W1'];
}

it('lists empty addresses then creates and unsets previous default', function () {
    $geo = seedGeoTree();
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/addresses')
        ->assertOk()->assertJsonPath('data', []);

    $payload = ['label' => 'Home', 'recipient_name' => 'A', 'phone' => '0900000000', ...$geo, 'address_line' => '1 Road', 'is_default' => true];
    $first = $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/addresses', $payload)
        ->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');

    $second = $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/addresses', [...$payload, 'label' => 'Office', 'is_default' => true])
        ->assertCreated()->assertJsonPath('data.is_default', true)->json('data.id');

    expect(CustomerAddress::query()->findOrFail($first)->is_default)->toBeFalse();
    expect(CustomerAddress::query()->findOrFail($second)->is_default)->toBeTrue();
});

it('updates patches and deletes owned address without reassigning default', function () {
    $geo = seedGeoTree();
    $customer = Customer::factory()->create();
    $id = $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/addresses', [
        'recipient_name' => 'A', 'phone' => '0900000000', ...$geo, 'address_line' => '1 Road', 'is_default' => true,
    ])->json('data.id');

    $this->actingAs($customer, 'customer')->patchJson("/api/v1/customer/addresses/{$id}", [
        'recipient_name' => 'B', 'phone' => '0900000000', ...$geo, 'address_line' => '2 Road', 'is_default' => true,
    ])->assertOk()->assertJsonPath('data.recipient_name', 'B');

    $this->actingAs($customer, 'customer')->deleteJson("/api/v1/customer/addresses/{$id}")
        ->assertOk()->assertJsonPath('data', null);

    $this->actingAs($customer, 'customer')->getJson('/api/v1/customer/addresses')
        ->assertOk()->assertJsonPath('data', []);
});

it('returns 404 for another customers address and 422 for invalid geo', function () {
    $geo = seedGeoTree();
    $owner = Customer::factory()->create();
    $other = Customer::factory()->create();
    $id = CustomerAddress::query()->create([
        'customer_id' => $owner->id, 'recipient_name' => 'A', 'phone' => '0900000000', ...$geo, 'address_line' => '1 Road',
    ])->id;

    $this->actingAs($other, 'customer')->patchJson("/api/v1/customer/addresses/{$id}", [
        'recipient_name' => 'X', 'phone' => '0900000000', ...$geo, 'address_line' => 'X',
    ])->assertNotFound();
    $this->actingAs($other, 'customer')->deleteJson("/api/v1/customer/addresses/{$id}")->assertNotFound();

    $this->actingAs($owner, 'customer')->postJson('/api/v1/customer/addresses', [
        'recipient_name' => 'A', 'phone' => '0900000000',
        'province_code' => 'P1', 'district_code' => 'D1', 'ward_code' => 'NOPE', 'address_line' => '1 Road',
    ])->assertUnprocessable();
});
