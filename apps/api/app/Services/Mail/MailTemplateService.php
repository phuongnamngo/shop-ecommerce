<?php

namespace App\Services\Mail;

use App\Models\NotificationTemplate;
use App\Notifications\TransactionalMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

final class MailTemplateService
{
    public const CHANNEL_EMAIL = 'email';

    /**
     * @var array<string, list<string>>
     */
    public const ALLOWLIST = [
        'order.placed' => ['order_number', 'grand_total', 'customer_name'],
        'order.paid' => ['order_number', 'grand_total', 'customer_name'],
        'order.shipped' => ['order_number', 'tracking_number', 'customer_name'],
        'auth.reset.customer' => ['reset_url', 'customer_name', 'expires_minutes'],
        'auth.reset.admin' => ['reset_url', 'admin_name', 'expires_minutes'],
    ];

    /**
     * @param  array<string, scalar|null>  $vars
     */
    public function interpolate(string $template, string $code, array $vars, bool $escapeHtml = true): string
    {
        foreach (self::ALLOWLIST[$code] ?? [] as $key) {
            if (! array_key_exists($key, $vars)) {
                continue;
            }
            $raw = (string) $vars[$key];
            $value = $escapeHtml
                ? htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : $raw;
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }

        return $template;
    }

    /**
     * @param  array<string, scalar|null>  $vars
     */
    public function inboxTitle(string $code, array $vars): string
    {
        $template = NotificationTemplate::query()
            ->where('code', $code)
            ->where('channel', self::CHANNEL_EMAIL)
            ->first();
        if ($template === null) {
            return '';
        }

        return $this->interpolate((string) $template->subject, $code, $vars, false);
    }

    /**
     * @param  array<string, scalar|null>  $vars
     */
    public function mailMessage(string $code, array $vars): ?MailMessage
    {
        $template = NotificationTemplate::query()
            ->where('code', $code)
            ->where('channel', self::CHANNEL_EMAIL)
            ->first();

        if ($template === null) {
            Log::warning('notification_template.missing', [
                'code' => $code,
                'channel' => self::CHANNEL_EMAIL,
            ]);

            return null;
        }

        $subject = $this->interpolate((string) $template->subject, $code, $vars);
        $body = $this->interpolate($template->body, $code, $vars);

        return (new MailMessage)
            ->subject($subject)
            ->view('mail.notification-html', ['body' => new HtmlString($body)]);
    }

    /**
     * @param  array<string, scalar|null>  $vars
     */
    public function send(object $notifiable, string $code, array $vars): void
    {
        if ($this->mailMessage($code, $vars) === null) {
            return;
        }
        $email = $notifiable->email ?? null;
        if (! is_string($email) || $email === '') {
            Log::warning('notification_mail.skip_no_email', ['code' => $code]);

            return;
        }
        $notifiable->notify(new TransactionalMail($code, $vars));
    }
}
