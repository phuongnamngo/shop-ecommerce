<?php

namespace App\Http\Controllers\Api\V1\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\Auth\RegisterRequest;
use App\Http\Resources\Identity\AuthProfile;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $customer = Customer::query()->create([
            'code' => (string) Str::ulid(),
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'password' => $request->string('password')->toString(),
            'status' => Customer::STATUS_ACTIVE,
            'email_verified_at' => null,
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        $customer->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::success(AuthProfile::customer($customer->fresh()), status: 201);
    }
}
