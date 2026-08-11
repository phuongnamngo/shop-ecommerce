<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ApiExceptionRenderer
{
    public static function shouldRender(Request $request): bool
    {
        return $request->is('api/*');
    }

    public static function render(Throwable $e, Request $request): mixed
    {
        if (! self::shouldRender($request)) {
            return null;
        }

        if ($e instanceof ValidationException) {
            return ApiResponse::validationErrors($e->errors());
        }

        if ($e instanceof AuthenticationException) {
            return ApiResponse::error(
                ErrorCode::AUTH_UNAUTHENTICATED,
                $e->getMessage() !== '' ? $e->getMessage() : 'Unauthenticated.',
                status: 401,
            );
        }

        if ($e instanceof AuthorizationException) {
            return ApiResponse::error(
                ErrorCode::AUTH_FORBIDDEN,
                $e->getMessage() !== '' ? $e->getMessage() : 'This action is unauthorized.',
                status: 403,
            );
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();

            if ($status === 419) {
                return ApiResponse::error(
                    ErrorCode::CSRF_TOKEN_MISMATCH,
                    $e->getMessage() !== '' ? $e->getMessage() : 'CSRF token mismatch.',
                    status: 419,
                );
            }

            if ($status === 429) {
                $retryAfter = method_exists($e, 'getHeaders')
                    ? ($e->getHeaders()['Retry-After'] ?? null)
                    : null;

                return ApiResponse::error(
                    ErrorCode::AUTH_THROTTLED,
                    $e->getMessage() !== '' ? $e->getMessage() : 'Too many requests.',
                    status: 429,
                    meta: $retryAfter !== null ? ['retry_after' => (int) $retryAfter] : [],
                );
            }

            if ($status === 403) {
                return ApiResponse::error(
                    ErrorCode::AUTH_FORBIDDEN,
                    $e->getMessage() !== '' ? $e->getMessage() : 'Forbidden.',
                    status: 403,
                );
            }
        }

        if ($e instanceof ThrottleRequestsException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

            return ApiResponse::error(
                ErrorCode::AUTH_THROTTLED,
                $e->getMessage() !== '' ? $e->getMessage() : 'Too many requests.',
                status: 429,
                meta: $retryAfter !== null ? ['retry_after' => (int) $retryAfter] : [],
            );
        }

        return null;
    }
}
