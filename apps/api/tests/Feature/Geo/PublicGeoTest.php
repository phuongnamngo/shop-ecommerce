<?php

use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Support\ErrorCode;

it('cascades public geo codes without auth', function () {
    $province = GeoProvince::query()->create(['code' => '99', 'name' => 'Test Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => '990', 'name' => 'Test District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => '99001', 'name' => 'Test Ward']);

    $this->getJson('/api/v1/geo/provinces')
        ->assertOk()
        ->assertJsonFragment(['code' => '99', 'name' => 'Test Province']);

    $this->getJson('/api/v1/geo/provinces/99/districts')
        ->assertOk()
        ->assertJsonPath('data.0.code', '990');

    $this->getJson('/api/v1/geo/districts/990/wards')
        ->assertOk()
        ->assertJsonPath('data.0.code', '99001');
});

it('returns not found for unknown geo parent codes', function () {
    $this->getJson('/api/v1/geo/provinces/NOPE/districts')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', ErrorCode::CATALOG_NOT_FOUND);

    $this->getJson('/api/v1/geo/districts/NOPE/wards')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', ErrorCode::CATALOG_NOT_FOUND);
});
