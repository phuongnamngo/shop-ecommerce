<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\VerifyPhoneOtpRequest;
use App\Http\Resources\Identity\AuthProfile;
use App\Services\Identity\PhoneOtpService;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PhoneOtpController extends Controller
{
    public function __construct(private readonly PhoneOtpService $otp) {}

    #[Response(200, 'OTP sent.', type: 'array{data: array{expires_at: string}, meta: object}')]
    public function send(Request $request): JsonResponse
    {
        return ApiResponse::success($this->otp->send($request->user('customer')));
    }

    #[Response(200, 'Phone verified.', type: 'array{data: array{id: int, code: string, name: string, email: string, phone: string|null, status: string, phone_verified_at: string|null}, meta: object}')]
    public function verify(VerifyPhoneOtpRequest $request): JsonResponse
    {
        $customer = $this->otp->verify($request->user('customer'), $request->string('code')->toString());

        return ApiResponse::success(AuthProfile::customer($customer));
    }
}
