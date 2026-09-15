<?php

namespace App\Services\Identity;

use App\Contracts\SmsGateway;
use App\Models\Customer;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Cache;

final class PhoneOtpService
{
    public const TTL_SECONDS = 300;

    public function __construct(private readonly SmsGateway $sms) {}

    /**
     * @return array{expires_at: string}
     */
    public function send(Customer $customer): array
    {
        $phone = $customer->phone;
        if ($phone === null || $phone === '') {
            throw new CommerceException(ErrorCode::PHONE_REQUIRED, 'A saved phone number is required.', 'phone');
        }
        if ($customer->phone_verified_at !== null) {
            throw new CommerceException(ErrorCode::PHONE_ALREADY_VERIFIED, 'Phone is already verified.', 'phone');
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $body = sprintf('Ma xac minh %s: %s. Hieu luc 5 phut.', (string) config('app.name'), $otp);
        $this->sms->send($phone, $body);

        $expires = now()->addSeconds(self::TTL_SECONDS);
        Cache::put($this->key($customer), [
            'hash' => hash_hmac('sha256', $otp, (string) config('app.key')),
            'phone' => $phone,
        ], $expires);

        return ['expires_at' => $expires->toISOString()];
    }

    public function verify(Customer $customer, string $code): Customer
    {
        if ($customer->phone_verified_at !== null) {
            throw new CommerceException(ErrorCode::PHONE_ALREADY_VERIFIED, 'Phone is already verified.', 'phone');
        }

        $payload = Cache::get($this->key($customer));
        if (! is_array($payload) || ($payload['phone'] ?? null) !== $customer->phone) {
            throw new CommerceException(ErrorCode::PHONE_OTP_EXPIRED, 'OTP has expired.', 'code');
        }

        $expected = hash_hmac('sha256', $code, (string) config('app.key'));
        if (! hash_equals((string) $payload['hash'], $expected)) {
            throw new CommerceException(ErrorCode::PHONE_OTP_INVALID, 'Invalid OTP.', 'code');
        }

        $customer->forceFill(['phone_verified_at' => now()])->save();
        Cache::forget($this->key($customer));

        return $customer->fresh();
    }

    private function key(Customer $customer): string
    {
        return 'phone-otp:customer:'.$customer->id;
    }
}
