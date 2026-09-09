<?php

namespace App\Http\Controllers\Api\V1\Geo;

use App\Http\Controllers\Controller;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Support\ApiResponse;
use App\Support\CatalogNotFound;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

final class GeoController extends Controller
{
    #[Response(200, 'Provinces.', type: 'array{data: list<array{code: string, name: string}>, meta: object}')]
    public function provinces(): JsonResponse
    {
        $rows = GeoProvince::query()->orderBy('code')->get(['code', 'name']);

        return ApiResponse::success($rows->map(fn (GeoProvince $row) => [
            'code' => $row->code,
            'name' => $row->name,
        ])->all());
    }

    #[Response(200, 'Districts in a province.', type: 'array{data: list<array{code: string, name: string}>, meta: object}')]
    public function districts(string $code): JsonResponse
    {
        $province = GeoProvince::query()->where('code', $code)->first();
        if ($province === null) {
            return CatalogNotFound::response();
        }

        $rows = GeoDistrict::query()->where('geo_province_id', $province->id)->orderBy('code')->get(['code', 'name']);

        return ApiResponse::success($rows->map(fn (GeoDistrict $row) => [
            'code' => $row->code,
            'name' => $row->name,
        ])->all());
    }

    #[Response(200, 'Wards in a district.', type: 'array{data: list<array{code: string, name: string}>, meta: object}')]
    public function wards(string $code): JsonResponse
    {
        $district = GeoDistrict::query()->where('code', $code)->first();
        if ($district === null) {
            return CatalogNotFound::response();
        }

        $rows = $district->wards()->orderBy('code')->get(['code', 'name']);

        return ApiResponse::success($rows->map(fn ($row) => [
            'code' => $row->code,
            'name' => $row->name,
        ])->all());
    }
}
