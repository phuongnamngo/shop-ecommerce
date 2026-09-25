<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Identity\AdminTwoFactorException;
use App\Services\Identity\AdminTwoFactorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TwoFactorSetupController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

    public function __invoke(): JsonResponse
    {
        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $payload = $this->twoFactor->setup($admin);
        } catch (AdminTwoFactorException $e) {
            return ApiResponse::error($e->errorCode, 'Two-factor authentication is already enabled.', status: $e->status);
        }

        return ApiResponse::success($payload);
    }
}
