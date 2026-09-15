<?php

use App\Models\NotificationTemplate;
use Database\Seeders\NotificationTemplateSeeder;

it('seeds five email templates idempotently', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $this->seed(NotificationTemplateSeeder::class);
    expect(NotificationTemplate::query()->where('channel', 'email')->count())->toBe(5)
        ->and(NotificationTemplate::query()->where('code', 'order.placed')->value('body'))->toContain('{{order_number}}')
        ->and(NotificationTemplate::query()->where('code', 'order.shipped')->value('body'))->toContain('{{tracking_number}}')
        ->and(NotificationTemplate::query()->where('code', 'auth.reset.customer')->value('body'))->toContain('{{reset_url}}');
});
