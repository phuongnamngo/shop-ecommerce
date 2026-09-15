<?php

use App\Models\NotificationTemplate;
use App\Services\Mail\MailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('replaces allowlisted placeholders and leaves unknown keys', function () {
    $out = (new MailTemplateService)->interpolate(
        'Hi {{customer_name}} {{unknown}} {{order_number}}',
        'order.placed',
        ['customer_name' => 'An', 'order_number' => 'ORD-1', 'grand_total' => '1'],
    );
    expect($out)->toBe('Hi An {{unknown}} ORD-1');
});

it('html-escapes replacement values', function () {
    $out = (new MailTemplateService)->interpolate(
        '<p>{{customer_name}}</p>',
        'order.placed',
        ['customer_name' => 'A <b>x</b>', 'order_number' => '1', 'grand_total' => '1'],
    );
    expect($out)->toBe('<p>A &lt;b&gt;x&lt;/b&gt;</p>');
});

it('returns null and logs when the email template is missing', function () {
    Log::spy();
    $mail = (new MailTemplateService)->mailMessage('order.placed', [
        'order_number' => '1',
        'grand_total' => '1',
        'customer_name' => 'A',
    ]);
    expect($mail)->toBeNull();
    Log::shouldHaveReceived('warning')
        ->withArgs(fn ($message, $context = []) => $message === 'notification_template.missing'
            && ($context['code'] ?? null) === 'order.placed');
});

it('builds a mail message from a stored template', function () {
    NotificationTemplate::factory()->create([
        'code' => 'order.placed',
        'channel' => 'email',
        'subject' => 'Đơn {{order_number}}',
        'body' => '<p>{{customer_name}}</p>',
    ]);

    $mail = (new MailTemplateService)->mailMessage('order.placed', [
        'order_number' => 'ORD-1',
        'grand_total' => '1',
        'customer_name' => 'An',
    ]);

    expect($mail)->not->toBeNull()
        ->and($mail->subject)->toBe('Đơn ORD-1')
        ->and((string) $mail->viewData['body'])->toBe('<p>An</p>');
});
