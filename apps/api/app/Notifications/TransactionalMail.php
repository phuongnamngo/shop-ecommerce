<?php

namespace App\Notifications;

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
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return app(MailTemplateService::class)->mailMessage($this->code, $this->vars)
            ?? (new MailMessage)
                ->subject('')
                ->view('mail.notification-html', ['body' => new HtmlString('')]);
    }
}
