<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\UpdateMeRequest;
use App\Http\Resources\Identity\AuthProfile;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    #[Response(200, 'Customer profile.', type: 'array{data: array{id: int, code: string, name: string, email: string, phone: string|null, status: string, phone_verified_at: string|null}, meta: object}')]
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(AuthProfile::customer($request->user('customer')));
    }

    #[Response(200, 'Updated customer profile.', type: 'array{data: array{id: int, code: string, name: string, email: string, phone: string|null, status: string, phone_verified_at: string|null}, meta: object}')]
    public function update(UpdateMeRequest $request): JsonResponse
    {
        $customer = $request->user('customer');
        $phone = $request->input('phone');
        $incoming = $phone === '' ? null : $phone;
        $phoneChanged = $incoming !== $customer->phone;
        $customer->fill([
            'name' => $request->string('name')->toString(),
            'phone' => $incoming,
        ]);
        if ($phoneChanged) {
            $customer->phone_verified_at = null;
        }
        $customer->save();

        return ApiResponse::success(AuthProfile::customer($customer->fresh()));
    }
}
