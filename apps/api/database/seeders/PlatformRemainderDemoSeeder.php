<?php

namespace Database\Seeders;

use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class PlatformRemainderDemoSeeder extends Seeder
{
    public function run(): void
    {
        // GHN sandbox Hà Nội / Ba Đình / Phúc Xá (ProvinceID / DistrictID / WardCode).
        // https://api.ghn.vn/home/docs/detail?id=77 (GetProvince / GetDistrict / GetWard)
        $province = GeoProvince::query()->firstOrCreate(
            ['code' => '201'],
            ['name' => 'Hà Nội'],
        );

        $district = GeoDistrict::query()->firstOrCreate(
            ['geo_province_id' => $province->id, 'code' => '1484'],
            ['name' => 'Ba Đình'],
        );

        GeoWard::query()->firstOrCreate(
            ['geo_district_id' => $district->id, 'code' => '1A0106'],
            ['name' => 'Phúc Xá'],
        );

        Setting::query()->firstOrCreate(
            ['key' => 'site.name'],
            ['value' => ['vi' => 'Watch Shop'], 'group' => 'site'],
        );

        Setting::query()->firstOrCreate(
            ['key' => 'currency.default'],
            ['value' => ['code' => 'VND'], 'group' => 'currency'],
        );
    }
}
