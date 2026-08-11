<?php

namespace App\Http\Controllers\Api\V1\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Auth\LoginRequest;
use App\Http\Resources\Identity\AuthProfile;
use App\Models\Customer;
use App\Support\ApiResponse;
use App\Support\AuthLogin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        [$customer, $error] = AuthLogin::resolveCustomer(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        if ($error !== null) {
            return ApiResponse::error($error, AuthLogin::messageFor($error), status: AuthLogin::statusFor($error));
        }

        /** @var Customer $customer */
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $customer->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::success(AuthProfile::customer($customer->fresh()));
    }
}
