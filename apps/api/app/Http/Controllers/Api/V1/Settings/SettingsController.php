<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ApiResponse;
use App\Support\SettingsAllowlist;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    #[Response(200, 'Public site settings.', type: 'array{data: array{site: array{name: string}, currency: array{code: string}}, meta: object}')]
    public function __invoke(): JsonResponse
    {
        $byKey = Setting::query()
            ->whereIn('key', SettingsAllowlist::keys())
            ->get()
            ->keyBy('key');

        $nameValue = $byKey->get(SettingsAllowlist::SITE_NAME)?->value;
        $codeValue = $byKey->get(SettingsAllowlist::CURRENCY_DEFAULT)?->value;

        return ApiResponse::success([
            'site' => [
                'name' => SettingsAllowlist::publicName(is_array($nameValue) ? $nameValue : null),
            ],
            'currency' => [
                'code' => SettingsAllowlist::publicCode(is_array($codeValue) ? $codeValue : null),
            ],
        ]);
    }
}
