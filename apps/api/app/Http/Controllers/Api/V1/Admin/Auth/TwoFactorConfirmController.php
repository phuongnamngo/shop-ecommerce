<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\Identity\AdminTwoFactorException;
use App\Services\Identity\AdminTwoFactorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorConfirmController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        /** @var AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        try {
            $codes = $this->twoFactor->confirm($admin, $data['code']);
        } catch (AdminTwoFactorException $e) {
            return ApiResponse::error($e->errorCode, 'Two-factor code is invalid.', status: $e->status);
        }

        return ApiResponse::success(['recovery_codes' => $codes]);
    }
}
