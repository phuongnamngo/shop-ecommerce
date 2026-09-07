<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class CatalogNotFound
{
    public static function response(): JsonResponse
    {
        return ApiResponse::error(
            ErrorCode::CATALOG_NOT_FOUND,
            'Not found.',
            status: 404,
        );
    }
}
