<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => $meta === [] ? (object) [] : $meta,
        ], $status);
    }

    public static function error(
        string $code,
        string $message,
        ?string $field = null,
        int $status = 400,
        array $meta = [],
    ): JsonResponse {
        return response()->json([
            'data' => null,
            'meta' => $meta === [] ? (object) [] : $meta,
            'errors' => [
                [
                    'code' => $code,
                    'message' => $message,
                    'field' => $field,
                ],
            ],
        ], $status);
    }

    /**
     * @param  array<string, array<int, string>|string>  $fieldMessages
     */
    public static function validationErrors(array $fieldMessages): JsonResponse
    {
        $errors = [];

        foreach ($fieldMessages as $field => $messages) {
            foreach ((array) $messages as $message) {
                $errors[] = [
                    'code' => ErrorCode::VALIDATION_FAILED,
                    'message' => $message,
                    'field' => $field,
                ];
            }
        }

        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'errors' => $errors,
        ], 422);
    }
}
