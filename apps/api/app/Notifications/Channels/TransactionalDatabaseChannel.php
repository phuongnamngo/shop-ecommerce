<?php

namespace App\Notifications\Channels;

use App\Models\NotificationTemplate;
use App\Notifications\TransactionalMail;
use App\Services\Mail\MailTemplateService;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\Notification;

final class TransactionalDatabaseChannel extends DatabaseChannel
{
    /**
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    protected function buildPayload($notifiable, Notification $notification): array
    {
        $payload = parent::buildPayload($notifiable, $notification);
        if ($notification instanceof TransactionalMail) {
            $payload['notification_template_id'] = NotificationTemplate::query()
                ->where('code', $notification->code)
                ->where('channel', MailTemplateService::CHANNEL_EMAIL)
                ->value('id');
        }

        return $payload;
    }
}
