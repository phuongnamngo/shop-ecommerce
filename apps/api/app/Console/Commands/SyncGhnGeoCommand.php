<?php

namespace App\Console\Commands;

use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class SyncGhnGeoCommand extends Command
{
    protected $signature = 'geo:sync-ghn';

    protected $description = 'Replace geo master data with GHN 3-level province/district/ward codes';

    public function handle(): int
    {
        $provinces = $this->post('/shiip/public-api/master-data/province', []);
        if (! $provinces->ok()) {
            $this->error('Failed to fetch GHN provinces.');

            return self::FAILURE;
        }

        $provinceRows = [];
        $districtRows = [];
        $wardRows = [];

        foreach ($provinces->json('data') ?? [] as $province) {
            $provinceId = (string) ($province['ProvinceID'] ?? '');
            $provinceName = (string) ($province['ProvinceName'] ?? '');
            if ($provinceId === '') {
                continue;
            }
            $provinceRows[] = ['code' => $provinceId, 'name' => $provinceName];

            $districts = $this->post('/shiip/public-api/master-data/district', [
                'province_id' => (int) $provinceId,
            ]);
            if (! $districts->ok()) {
                $this->error("Failed to fetch GHN districts for province {$provinceId}.");

                return self::FAILURE;
            }

            foreach ($districts->json('data') ?? [] as $district) {
                $districtId = (string) ($district['DistrictID'] ?? '');
                $districtName = (string) ($district['DistrictName'] ?? '');
                if ($districtId === '') {
                    continue;
                }
                $districtRows[] = [
                    'code' => $districtId,
                    'name' => $districtName,
                    'province_code' => $provinceId,
                ];

                $wards = $this->post('/shiip/public-api/master-data/ward', [
                    'district_id' => (int) $districtId,
                ]);
                if (! $wards->ok()) {
                    $this->error("Failed to fetch GHN wards for district {$districtId}.");

                    return self::FAILURE;
                }

                foreach ($wards->json('data') ?? [] as $ward) {
                    $wardCode = (string) ($ward['WardCode'] ?? '');
                    $wardName = (string) ($ward['WardName'] ?? '');
                    if ($wardCode === '') {
                        continue;
                    }
                    $wardRows[] = [
                        'code' => $wardCode,
                        'name' => $wardName,
                        'district_code' => $districtId,
                    ];
                }
            }
        }

        if ($provinceRows === []) {
            $this->error('GHN location API returned no provinces.');

            return self::FAILURE;
        }

        if ($districtRows === []) {
            $this->error('GHN location API returned no districts (possible 2-level master). Aborting.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($provinceRows, $districtRows, $wardRows): void {
            $provinceIds = [];
            foreach ($provinceRows as $row) {
                $province = GeoProvince::withTrashed()->where('code', $row['code'])->first();
                if ($province === null) {
                    $province = GeoProvince::query()->create(['code' => $row['code'], 'name' => $row['name']]);
                } else {
                    if ($province->trashed()) {
                        $province->restore();
                    }
                    $province->update(['name' => $row['name']]);
                }
                $provinceIds[$row['code']] = $province->id;
            }

            $districtIds = [];
            foreach ($districtRows as $row) {
                $district = GeoDistrict::withTrashed()->where('code', $row['code'])->first();
                $provinceId = $provinceIds[$row['province_code']];
                if ($district === null) {
                    $district = GeoDistrict::query()->create([
                        'geo_province_id' => $provinceId,
                        'code' => $row['code'],
                        'name' => $row['name'],
                    ]);
                } else {
                    if ($district->trashed()) {
                        $district->restore();
                    }
                    $district->update([
                        'geo_province_id' => $provinceId,
                        'name' => $row['name'],
                    ]);
                }
                $districtIds[$row['code']] = $district->id;
            }

            foreach ($wardRows as $row) {
                $ward = GeoWard::withTrashed()->where('code', $row['code'])->first();
                $districtId = $districtIds[$row['district_code']];
                if ($ward === null) {
                    GeoWard::query()->create([
                        'geo_district_id' => $districtId,
                        'code' => $row['code'],
                        'name' => $row['name'],
                    ]);
                } else {
                    if ($ward->trashed()) {
                        $ward->restore();
                    }
                    $ward->update([
                        'geo_district_id' => $districtId,
                        'name' => $row['name'],
                    ]);
                }
            }

            $keepProvinces = array_column($provinceRows, 'code');
            $keepDistricts = array_column($districtRows, 'code');
            $keepWards = array_column($wardRows, 'code');

            GeoWard::query()->whereNotIn('code', $keepWards)->delete();
            GeoDistrict::query()->whereNotIn('code', $keepDistricts)->delete();
            GeoProvince::query()->whereNotIn('code', $keepProvinces)->delete();
        });

        $this->info('Synced GHN geo master.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function post(string $path, array $body): Response
    {
        return Http::asJson()
            ->baseUrl(rtrim((string) config('commerce.ghn.base_url'), '/'))
            ->withHeaders([
                'Token' => (string) config('commerce.ghn.token'),
                'ShopId' => (string) config('commerce.ghn.shop_id'),
            ])
            ->post($path, $body);
    }
}
