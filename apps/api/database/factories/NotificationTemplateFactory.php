<?php

namespace Database\Factories;

use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'tpl.'.Str::lower(Str::random(8)),
            'channel' => 'email',
            'subject' => 'Subject {{order_number}}',
            'body' => '<p>{{order_number}}</p>',
        ];
    }
}
