<?php

use App\Support\OpsAlert;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('posts slack and telegram when both channels are configured', function () {
    Http::fake();
    config([
        'ops.slack_webhook' => 'https://hooks.example.test/slack',
        'ops.telegram_bot_token' => 'tok',
        'ops.telegram_chat_id' => '99',
    ]);

    OpsAlert::send('ping');

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.test/slack'
        && $request['text'] === 'ping');
    Http::assertSent(fn ($request) => $request->url() === 'https://api.telegram.org/bottok/sendMessage'
        && $request['chat_id'] === '99'
        && $request['text'] === 'ping');
});

it('does not call http when alert env is empty', function () {
    Http::fake();
    config([
        'ops.slack_webhook' => null,
        'ops.telegram_bot_token' => null,
        'ops.telegram_chat_id' => null,
    ]);

    OpsAlert::send('ping');

    Http::assertNothingSent();
});

it('skips telegram when only one telegram env is set', function () {
    Http::fake();
    config([
        'ops.slack_webhook' => null,
        'ops.telegram_bot_token' => 'tok',
        'ops.telegram_chat_id' => null,
    ]);

    OpsAlert::send('ping');

    Http::assertNothingSent();
});

it('logs a warning and does not throw when the webhook connection fails', function () {
    Http::fake(function () {
        throw new ConnectionException('down');
    });
    Log::spy();
    config([
        'ops.slack_webhook' => 'https://hooks.example.test/slack',
        'ops.telegram_bot_token' => null,
        'ops.telegram_chat_id' => null,
    ]);

    OpsAlert::send('ping');

    Log::shouldHaveReceived('warning');
});
