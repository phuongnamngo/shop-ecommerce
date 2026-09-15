<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use App\Support\CommerceException;
use App\Support\ErrorCode;

final class UnavailableSmsGateway implements SmsGateway
{
    public function send(string $to, string $body): void
    {
        throw new CommerceException(ErrorCode::SMS_UNAVAILABLE, 'SMS gateway is unavailable.', status: 503);
    }
}
