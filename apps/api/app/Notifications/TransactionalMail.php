<?php

namespace App\Notifications;

use App\Notifications\Channels\TransactionalDatabaseChannel;
use App\Services\Mail\MailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

final class TransactionalMail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, scalar|null>  $vars
     */
    public function __construct(
        public readonly string $code,
        public readonly array $vars,
    ) {
        if (! app()->runningUnitTests()) {
            $this->afterCommit();
        }
    }

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', TransactionalDatabaseChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return app(MailTemplateService::class)->mailMessage($this->code, $this->vars)
            ?? (new MailMessage)
                ->subject('')
                ->view('mail.notification-html', ['body' => new HtmlString('')]);
    }

    /**
     * @return array{code: string, title: string, order_id: int}
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'title' => app(MailTemplateService::class)->inboxTitle($this->code, $this->vars),
            'order_id' => (int) ($this->vars['order_id'] ?? 0),
        ];
    }
}
