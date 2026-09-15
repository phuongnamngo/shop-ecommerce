<?php

namespace App\Notifications;

use App\Services\Mail\MailTemplateService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class CustomerResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct(#[\SensitiveParameter] $token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }

    public function resetUrl($notifiable): string
    {
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : $notifiable->email;

        return rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?token='.urlencode($this->token)
            .'&email='.urlencode($email);
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = app(MailTemplateService::class)->mailMessage('auth.reset.customer', [
            'reset_url' => $this->resetUrl($notifiable),
            'customer_name' => (string) $notifiable->name,
            'expires_minutes' => (string) config('auth.passwords.customers.expire'),
        ]);
        if ($mail === null) {
            return (new MailMessage)
                ->subject('')
                ->view('mail.notification-html', ['body' => new HtmlString('')]);
        }

        return $mail;
    }
}
