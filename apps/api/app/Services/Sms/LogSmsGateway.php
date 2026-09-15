<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Log;

final class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $body): void
    {
        Log::info('sms.send', ['to' => $to, 'body' => $body]);
    }
}
