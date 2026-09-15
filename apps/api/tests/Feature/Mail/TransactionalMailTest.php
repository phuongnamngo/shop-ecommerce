<?php

use App\Models\Customer;
use App\Notifications\TransactionalMail;
use App\Services\Mail\MailTemplateService;
use Database\Seeders\NotificationTemplateSeeder;
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
