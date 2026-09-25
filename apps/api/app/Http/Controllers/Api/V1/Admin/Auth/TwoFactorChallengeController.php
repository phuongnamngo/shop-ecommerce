<?php

namespace App\Http\Controllers\Api\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Identity\AuthProfile;
use App\Services\Identity\AdminTwoFactorException;
use App\Services\Identity\AdminTwoFactorService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly AdminTwoFactorService $twoFactor) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'two_factor_token' => ['required', 'string', 'size:64'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        try {
            $admin = $this->twoFactor->consumeChallenge($data['two_factor_token'], $data['code']);
        } catch (AdminTwoFactorException $e) {
            return ApiResponse::error($e->errorCode, $this->message($e->errorCode), status: $e->status);
        }

        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();
        $admin->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::success(AuthProfile::admin($admin->fresh()));
    }

    private function message(string $code): string
    {
        return $code === 'AUTH_TWO_FACTOR_INVALID'
            ? 'Two-factor code is invalid.'
            : 'Two-factor authentication is required.';
    }
}
