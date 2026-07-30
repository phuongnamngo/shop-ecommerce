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
        $province = GeoProvince::query()->firstOrCreate(
            ['code' => '01'],
            ['name' => 'Hà Nội'],
        );

        $district = GeoDistrict::query()->firstOrCreate(
            ['geo_province_id' => $province->id, 'code' => '001'],
            ['name' => 'Ba Đình'],
        );

        GeoWard::query()->firstOrCreate(
            ['geo_district_id' => $district->id, 'code' => '00001'],
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
