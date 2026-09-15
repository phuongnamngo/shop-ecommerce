<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class CmsNotFound
{
    public static function page(): JsonResponse
    {
        return ApiResponse::error(
            ErrorCode::CMS_PAGE_NOT_FOUND,
            'Not found.',
            status: 404,
        );
    }

    public static function banner(): JsonResponse
    {
        return ApiResponse::error(
            ErrorCode::CMS_BANNER_NOT_FOUND,
            'Not found.',
            status: 404,
        );
    }
}
