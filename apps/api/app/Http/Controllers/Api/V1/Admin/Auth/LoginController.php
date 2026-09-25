<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Auth\LoginRequest;
use App\Http\Resources\Identity\AuthProfile;
use App\Models\AdminUser;
use App\Services\Identity\AdminTwoFactorService;
use App\Support\ApiResponse;
use App\Support\AuthLogin;
use App\Support\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        [$admin, $error] = AuthLogin::resolveAdmin(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if ($error !== null) {
            return ApiResponse::error($error, AuthLogin::messageFor($error), status: AuthLogin::statusFor($error));
        }

        /** @var AdminUser $admin */
        if ($admin->two_factor_confirmed_at !== null) {
            return ApiResponse::error(
                ErrorCode::AUTH_TWO_FACTOR_REQUIRED,
                'Two-factor authentication is required.',
                status: 401,
                meta: ['two_factor_token' => $this->twoFactor->beginChallenge($admin)],
            );
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::success(AuthProfile::admin($admin->fresh()));
    }
}
