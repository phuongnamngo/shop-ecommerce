<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'order.placed' => [
                'subject' => 'Đơn hàng {{order_number}} đã được đặt',
                'body' => '<p>Xin chào {{customer_name}},</p><p>Đơn <strong>{{order_number}}</strong> đã đặt. Tổng: {{grand_total}}.</p>',
            ],
            'order.paid' => [
                'subject' => 'Đơn hàng {{order_number}} đã thanh toán',
                'body' => '<p>Xin chào {{customer_name}},</p><p>Đơn <strong>{{order_number}}</strong> đã thanh toán. Tổng: {{grand_total}}.</p>',
            ],
            'order.shipped' => [
                'subject' => 'Đơn hàng {{order_number}} đang giao',
                'body' => '<p>Xin chào {{customer_name}},</p><p>Đơn <strong>{{order_number}}</strong> đang giao. Mã vận đơn: {{tracking_number}}.</p>',
            ],
            'auth.reset.customer' => [
                'subject' => 'Đặt lại mật khẩu',
                'body' => '<p>Xin chào {{customer_name}},</p><p><a href="{{reset_url}}">Đặt lại mật khẩu</a></p><p>Link hết hạn sau {{expires_minutes}} phút.</p>',
            ],
            'auth.reset.admin' => [
                'subject' => 'Đặt lại mật khẩu admin',
                'body' => '<p>Xin chào {{admin_name}},</p><p><a href="{{reset_url}}">Đặt lại mật khẩu</a></p><p>Link hết hạn sau {{expires_minutes}} phút.</p>',
            ],
        ];

        foreach ($templates as $code => $attrs) {
            NotificationTemplate::query()->firstOrCreate(
                ['code' => $code],
                [
                    'channel' => 'email',
                    'subject' => $attrs['subject'],
                    'body' => $attrs['body'],
                ],
            );
        }
    }
}
