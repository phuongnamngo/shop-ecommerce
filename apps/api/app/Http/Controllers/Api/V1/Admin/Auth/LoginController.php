<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Auth\LoginRequest;
use App\Http\Resources\Identity\AuthProfile;
use App\Models\AdminUser;
use App\Support\ApiResponse;
use App\Support\AuthLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
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
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::success(AuthProfile::admin($admin->fresh()));
    }
}
