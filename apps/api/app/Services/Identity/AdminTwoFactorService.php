<?php

namespace App\Services\Identity;

use App\Models\AdminTwoFactorRecoveryCode;
use App\Models\AdminUser;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

final class AdminTwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function beginChallenge(AdminUser $admin): string
    {
        $token = Str::random(64);
        Cache::put($this->cacheKey($token), $admin->id, 300);

        return $token;
    }

    public function consumeChallenge(string $token, string $code): AdminUser
    {
        $adminId = Cache::get($this->cacheKey($token));
        $admin = is_numeric($adminId)
            ? AdminUser::query()->find((int) $adminId)
            : null;

        if ($admin === null || $admin->two_factor_confirmed_at === null || $admin->two_factor_secret === null) {
            throw new AdminTwoFactorException(ErrorCode::AUTH_TWO_FACTOR_REQUIRED, 401);
        }

        if ($this->google2fa->verifyKey($admin->two_factor_secret, $code, 1)) {
            Cache::forget($this->cacheKey($token));

            return $admin;
        }

        $hash = hash('sha256', $code);
        $recovery = AdminTwoFactorRecoveryCode::query()
            ->where('admin_user_id', $admin->id)
            ->where('code_hash', $hash)
            ->first();

        if ($recovery === null) {
            throw new AdminTwoFactorException(ErrorCode::AUTH_TWO_FACTOR_INVALID, 422);
        }

        $recovery->delete();
        Cache::forget($this->cacheKey($token));

        return $admin;
    }

    /**
     * @return array{secret: string, otpauth_uri: string}
     */
    public function setup(AdminUser $admin): array
    {
        if ($admin->two_factor_confirmed_at !== null) {
            throw new AdminTwoFactorException(ErrorCode::AUTH_TWO_FACTOR_ALREADY_ENABLED, 409);
        }

        $secret = $this->google2fa->generateSecretKey();
        $admin->forceFill(['two_factor_secret' => $secret])->save();

        return [
            'secret' => $secret,
            'otpauth_uri' => 'otpauth://totp/'.rawurlencode('Watch:'.$admin->email).'?secret='.$secret.'&issuer=Watch',
        ];
    }

    /**
     * @return list<string>
     */
    public function confirm(AdminUser $admin, string $code): array
    {
        if ($admin->two_factor_secret === null || $admin->two_factor_confirmed_at !== null) {
            throw new AdminTwoFactorException(ErrorCode::AUTH_TWO_FACTOR_INVALID, 422);
        }

        if (! $this->google2fa->verifyKey($admin->two_factor_secret, $code, 1)) {
            throw new AdminTwoFactorException(ErrorCode::AUTH_TWO_FACTOR_INVALID, 422);
        }

        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(5));
        }

        foreach ($codes as $plain) {
            AdminTwoFactorRecoveryCode::query()->create([
                'admin_user_id' => $admin->id,
                'code_hash' => hash('sha256', $plain),
            ]);
        }

        $admin->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $codes;
    }

    private function cacheKey(string $token): string
    {
        return 'admin.2fa.challenge.'.$token;
    }
}
