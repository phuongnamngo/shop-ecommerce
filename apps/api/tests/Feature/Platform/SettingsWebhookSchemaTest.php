<?php

use App\Models\Setting;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

it('enforces unique settings key', function () {
    Setting::query()->create([
        'key' => 'currency.default',
        'value' => ['code' => 'VND'],
        'group' => 'currency',
    ]);

    expect(fn () => Setting::query()->create([
        'key' => 'currency.default',
        'value' => ['code' => 'USD'],
        'group' => 'currency',
    ]))->toThrow(QueryException::class);
});

it('enforces unique webhook idempotency_key', function () {
    $key = (string) Str::uuid();

    WebhookEvent::query()->create([
        'provider' => 'vnpay',
        'event_type' => 'payment.succeeded',
        'payload' => ['ok' => true],
        'status' => 'received',
        'idempotency_key' => $key,
    ]);

    expect(fn () => WebhookEvent::query()->create([
        'provider' => 'vnpay',
        'event_type' => 'payment.succeeded',
        'payload' => ['ok' => true],
        'status' => 'received',
        'idempotency_key' => $key,
    ]))->toThrow(QueryException::class);
});
