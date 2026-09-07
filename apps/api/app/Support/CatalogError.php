<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class CatalogError
{
    public static function from(CatalogException $e): JsonResponse
    {
        return ApiResponse::error($e->errorCode, $e->getMessage(), $e->field, $e->status);
    }
}
