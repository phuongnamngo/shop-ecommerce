<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;

final class FakeSmsGateway implements SmsGateway
{
    public ?string $lastTo = null;

    public ?string $lastBody = null;

    public ?string $lastOtp = null;

    public function send(string $to, string $body): void
    {
        $this->lastTo = $to;
        $this->lastBody = $body;
        $this->lastOtp = preg_match('/\b(\d{6})\b/', $body, $matches) === 1
            ? $matches[1]
            : null;
    }
}
