<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Settings\UpdateSettingRequest;
use App\Http\Resources\Settings\AdminSettingResource;
use App\Models\Setting;
use App\Support\ApiResponse;
use App\Support\SettingsAllowlist;
use App\Support\SettingsNotFound;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    #[Response(200, 'Allowlisted settings.', type: 'array{data: list<array{key: string, group: string|null, value: array<string, mixed>|null}>, meta: object}')]
    public function index(): JsonResponse
    {
        $settings = Setting::query()
            ->whereIn('key', SettingsAllowlist::keys())
            ->orderBy('key')
            ->get(['key', 'group', 'value']);

        return ApiResponse::success(
            AdminSettingResource::collection($settings)->resolve(),
        );
    }

    #[Response(200, 'Updated setting.', type: 'array{data: array{key: string, group: string|null, value: array<string, mixed>|null}, meta: object}')]
    public function update(UpdateSettingRequest $request, string $key): JsonResponse
    {
        if (! SettingsAllowlist::isAllowed($key)) {
            return SettingsNotFound::response();
        }

        $setting = Setting::query()->where('key', $key)->first();
        if ($setting === null) {
            return SettingsNotFound::response();
        }

        $setting->update(['value' => $request->validated('value')]);

        return ApiResponse::success(AdminSettingResource::make($setting->refresh())->resolve());
    }
}
