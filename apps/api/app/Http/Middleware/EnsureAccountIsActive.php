<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Models\Customer;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next, ?string $guard = null): Response
    {
        $user = $guard
            ? $request->user($guard)
            : $request->user();

        if ($user === null) {
            return ApiResponse::error(
                ErrorCode::AUTH_UNAUTHENTICATED,
                'Unauthenticated.',
                status: 401,
            );
        }

        $status = $user->status ?? null;

        if ($status === Customer::STATUS_BANNED || $status === AdminUser::STATUS_BANNED) {
            return ApiResponse::error(
                ErrorCode::AUTH_ACCOUNT_BANNED,
                'Account is banned.',
                status: 403,
            );
        }

        if (! method_exists($user, 'isActive') || ! $user->isActive()) {
            return ApiResponse::error(
                ErrorCode::AUTH_ACCOUNT_INACTIVE,
                'Account is not active.',
                status: 403,
            );
        }

        return $next($request);
    }
}
