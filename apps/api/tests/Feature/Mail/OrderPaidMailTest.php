<?php

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Notifications\TransactionalMail;
use App\Services\Payment\FakePaymentGateway;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(NotificationTemplateSeeder::class);
});

it('sends order.paid once when admin marks a customer order paid', function () {
    Notification::fake();
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $customer = Customer::factory()->create();
    $method = PaymentMethod::query()->create(['code' => 'cod', 'name' => 'COD', 'is_active' => true]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'customer_id' => $customer->id,
        'grand_total' => 100000,
    ]);
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'cod',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => $order->grand_total,
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])
        ->assertOk();

    Notification::assertSentTo($customer, TransactionalMail::class, function (TransactionalMail $n) {
        return $n->code === 'order.paid';
    });
    Notification::assertSentToTimes($customer, TransactionalMail::class, 1);

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])
        ->assertConflict();

    Notification::assertSentToTimes($customer, TransactionalMail::class, 1);
});

it('sends order.paid once for VNPay IPN and not again on retry', function () {
    Notification::fake();
    $customer = Customer::factory()->create();
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret']);
    $method = PaymentMethod::query()->create(['code' => 'vnpay', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'number' => 'ORD-PAIDMAIL',
        'customer_id' => $customer->id,
        'grand_total' => 100000,
    ]);
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'vnpay',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 100000,
        'status' => 'pending',
    ]);
    $gateway = new FakePaymentGateway;
    $payload = [
        'vnp_ResponseCode' => '00',
        'vnp_TxnRef' => 'ORD-PAIDMAIL',
        'vnp_TransactionNo' => 'VN123',
        'vnp_TransactionDate' => '20260914120000',
    ];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))
        ->assertOk()
        ->assertSee('Confirm Success');
    Notification::assertSentToTimes($customer, TransactionalMail::class, 1);
    Notification::assertSentTo($customer, TransactionalMail::class, function (TransactionalMail $n) {
        return $n->code === 'order.paid';
    });

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))->assertOk();
    Notification::assertSentToTimes($customer, TransactionalMail::class, 1);
});

it('does not send order.paid for a guest order', function () {
    Notification::fake();
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $method = PaymentMethod::query()->create(['code' => 'cod', 'name' => 'COD', 'is_active' => true]);
    $order = Order::factory()->create(['status' => 'pending', 'grand_total' => 100000]);
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'cod',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => $order->grand_total,
        'status' => 'pending',
    ]);

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])
        ->assertOk();

    Notification::assertNothingSent();
});
