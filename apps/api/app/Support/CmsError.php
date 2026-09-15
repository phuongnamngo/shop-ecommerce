<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class CmsError
{
    public static function from(CmsException $e): JsonResponse
    {
        return ApiResponse::error($e->errorCode, $e->getMessage(), $e->field, $e->status);
    }
}
