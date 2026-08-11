<?php

namespace App\Http\Controllers\Api\V1\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Auth\ResetPasswordRequest;
use App\Support\ApiResponse;
use App\Support\ErrorCode;
use App\Support\SessionInvalidator;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                SessionInvalidator::forgetUserSessions($user->id);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(
                ErrorCode::AUTH_RESET_TOKEN_INVALID,
                'This password reset token is invalid.',
                status: 422,
            );
        }

        return ApiResponse::success([
            'message' => 'Password has been reset.',
        ]);
    }
}
