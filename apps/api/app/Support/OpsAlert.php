<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tested send channel for Slack and Telegram. Not production on-call.
 * Do not hook this to every 4xx or 5xx.
 */
final class OpsAlert
{
    public static function send(string $message): void
    {
        $slack = config('ops.slack_webhook');
        if (is_string($slack) && $slack !== '') {
            self::post($slack, ['text' => $message]);
        }

        $token = config('ops.telegram_bot_token');
        $chat = config('ops.telegram_chat_id');
        if (is_string($token) && $token !== '' && is_string($chat) && $chat !== '') {
            self::post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                'chat_id' => $chat,
                'text' => $message,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $body
     */
    private static function post(string $url, array $body): void
    {
        try {
            $response = Http::timeout(3)->post($url, $body);
            if ($response->failed()) {
                Log::warning('ops alert http failed', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('ops alert http failed', [
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
