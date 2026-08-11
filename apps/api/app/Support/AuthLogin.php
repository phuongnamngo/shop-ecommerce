<?php

namespace App\Support;

use App\Models\AdminUser;
use App\Models\Customer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

final class AuthLogin
{
    /**
     * @return array{0: ?Authenticatable, 1: ?string}
     */
    public static function resolveCustomer(string $email, string $password): array
    {
        $customer = Customer::query()->where('email', $email)->first();

        if ($customer === null || ! Hash::check($password, $customer->password)) {
            return [null, ErrorCode::AUTH_INVALID_CREDENTIALS];
        }

        if ($customer->status === Customer::STATUS_BANNED) {
            return [null, ErrorCode::AUTH_ACCOUNT_BANNED];
        }

        if (! $customer->isActive()) {
            return [null, ErrorCode::AUTH_ACCOUNT_INACTIVE];
        }

        return [$customer, null];
    }

    /**
     * @return array{0: ?Authenticatable, 1: ?string}
     */
    public static function resolveAdmin(string $email, string $password): array
    {
        $admin = AdminUser::query()->where('email', $email)->first();

        if ($admin === null || ! Hash::check($password, $admin->password)) {
            return [null, ErrorCode::AUTH_INVALID_CREDENTIALS];
        }

        if ($admin->status === AdminUser::STATUS_BANNED) {
            return [null, ErrorCode::AUTH_ACCOUNT_BANNED];
        }

        if (! $admin->isActive()) {
            return [null, ErrorCode::AUTH_ACCOUNT_INACTIVE];
        }

        return [$admin, null];
    }

    public static function messageFor(string $code): string
    {
        return match ($code) {
            ErrorCode::AUTH_INVALID_CREDENTIALS => 'Invalid credentials.',
            ErrorCode::AUTH_ACCOUNT_BANNED => 'Account is banned.',
            ErrorCode::AUTH_ACCOUNT_INACTIVE => 'Account is not active.',
            default => 'Authentication failed.',
        };
    }

    public static function statusFor(string $code): int
    {
        return match ($code) {
            ErrorCode::AUTH_INVALID_CREDENTIALS => 401,
            ErrorCode::AUTH_ACCOUNT_BANNED, ErrorCode::AUTH_ACCOUNT_INACTIVE => 403,
            default => 401,
        };
    }
}
