<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPassword extends ResetPassword
{
    public function resetUrl($notifiable): string
    {
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : $notifiable->email;

        return rtrim((string) config('app.frontend_url'), '/')
            .'/admin/reset-password?token='.urlencode($this->token)
            .'&email='.urlencode($email);
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Admin Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your admin account.')
            ->action('Reset Password', $this->resetUrl($notifiable))
            ->line('This password reset link will expire in '.config('auth.passwords.admin_users.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
