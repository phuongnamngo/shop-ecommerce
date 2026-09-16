<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class SettingsNotFound
{
    public static function response(): JsonResponse
    {
        return ApiResponse::error(
            ErrorCode::SETTINGS_NOT_FOUND,
            'Not found.',
            status: 404,
        );
    }
}
