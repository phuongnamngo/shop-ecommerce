<?php

use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

function fakeGhnLocationMaster(): void
{
    config([
        'commerce.ghn.token' => 'ghn-token',
        'commerce.ghn.shop_id' => '123',
        'commerce.ghn.base_url' => 'https://dev-online-gateway.ghn.vn',
    ]);

    Http::fake(function ($request) {
        $url = $request->url();
        if (str_contains($url, '/shiip/public-api/master-data/province')) {
            return Http::response([
                'code' => 200,
                'data' => [
                    ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội'],
                ],
            ]);
        }
        if (str_contains($url, '/shiip/public-api/master-data/district')) {
            return Http::response([
                'code' => 200,
                'data' => [
                    ['DistrictID' => 1484, 'DistrictName' => 'Ba Đình', 'ProvinceID' => 201],
                ],
            ]);
        }
        if (str_contains($url, '/shiip/public-api/master-data/ward')) {
            return Http::response([
                'code' => 200,
                'data' => [
                    ['WardCode' => '1A0106', 'WardName' => 'Phúc Xá', 'DistrictID' => 1484],
                ],
            ]);
        }

        return Http::response(['code' => 404], 404);
    });
}

it('upserts a three-level GHN master and does not duplicate on a second sync', function () {
    fakeGhnLocationMaster();

    expect(Artisan::call('geo:sync-ghn'))->toBe(0);
    expect(GeoProvince::query()->count())->toBe(1)
        ->and(GeoDistrict::query()->count())->toBe(1)
        ->and(GeoWard::query()->count())->toBe(1);
    expect(GeoProvince::query()->where('code', '201')->where('name', 'Hà Nội')->exists())->toBeTrue();
    expect(GeoDistrict::query()->where('code', '1484')->where('name', 'Ba Đình')->exists())->toBeTrue();
    expect(GeoWard::query()->where('code', '1A0106')->where('name', 'Phúc Xá')->exists())->toBeTrue();

    expect(Artisan::call('geo:sync-ghn'))->toBe(0);
    expect(GeoProvince::query()->count())->toBe(1)
        ->and(GeoDistrict::query()->count())->toBe(1)
        ->and(GeoWard::query()->count())->toBe(1);
});

it('restores a soft-deleted GHN ward on sync', function () {
    fakeGhnLocationMaster();
    Artisan::call('geo:sync-ghn');
    $ward = GeoWard::query()->where('code', '1A0106')->firstOrFail();
    $ward->delete();
    expect(GeoWard::query()->where('code', '1A0106')->exists())->toBeFalse();

    expect(Artisan::call('geo:sync-ghn'))->toBe(0);
    expect(GeoWard::withTrashed()->where('code', '1A0106')->whereNull('deleted_at')->exists())->toBeTrue();
});

it('deletes leftover GSO codes that are not on the GHN master', function () {
    fakeGhnLocationMaster();
    $province = GeoProvince::query()->create(['code' => '01', 'name' => 'Hà Nội GSO']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => '001', 'name' => 'Ba Đình GSO']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => '00001', 'name' => 'Phúc Xá GSO']);

    expect(Artisan::call('geo:sync-ghn'))->toBe(0);
    expect(GeoProvince::query()->where('code', '01')->exists())->toBeFalse();
    expect(GeoDistrict::query()->where('code', '001')->exists())->toBeFalse();
    expect(GeoWard::query()->where('code', '00001')->exists())->toBeFalse();
    expect(GeoProvince::query()->where('code', '201')->exists())->toBeTrue();
});
