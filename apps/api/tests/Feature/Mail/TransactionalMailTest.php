<?php

use App\Models\Customer;
use App\Notifications\TransactionalMail;
use App\Services\Mail\MailTemplateService;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

it('notifies TransactionalMail when the template exists', function () {
    $this->seed(NotificationTemplateSeeder::class);
    Notification::fake();
    $customer = Customer::factory()->create();
    app(MailTemplateService::class)->send($customer, 'order.placed', [
        'order_number' => 'ORD-1',
        'grand_total' => '1000.00',
        'customer_name' => $customer->name,
    ]);
    Notification::assertSentTo($customer, TransactionalMail::class, function (TransactionalMail $n) {
        return $n->code === 'order.placed';
    });
});

it('does not notify when the template is missing', function () {
    Notification::fake();
    $customer = Customer::factory()->create();
    app(MailTemplateService::class)->send($customer, 'order.placed', [
        'order_number' => 'ORD-1',
        'grand_total' => '1',
        'customer_name' => 'A',
    ]);
    Notification::assertNothingSent();
});

it('writes a notifications row with plain title and template id', function () {
    $this->seed(NotificationTemplateSeeder::class);
    $customer = Customer::factory()->create(['name' => 'A & B']);
    app(MailTemplateService::class)->send($customer, 'order.placed', [
        'order_number' => 'ORD-1',
        'grand_total' => '1000.00',
        'customer_name' => 'A & B',
        'order_id' => 99,
    ]);

    expect(DB::table('notifications')->count())->toBe(1);
    $row = DB::table('notifications')->first();
    $data = json_decode((string) $row->data, true, flags: JSON_THROW_ON_ERROR);
    expect($data['code'])->toBe('order.placed')
        ->and($data['title'])->toBe('Đơn hàng ORD-1 đã được đặt')
        ->and($data['order_id'])->toBe(99)
        ->and($row->notification_template_id)->not->toBeNull()
        ->and($row->notifiable_id)->toBe($customer->id);
});

it('does not write a notifications row when the template is missing', function () {
    $customer = Customer::factory()->create();
    app(MailTemplateService::class)->send($customer, 'order.placed', [
        'order_number' => 'ORD-1',
        'grand_total' => '1',
        'customer_name' => 'A',
        'order_id' => 1,
    ]);
    expect(DB::table('notifications')->count())->toBe(0);
});
