<?php

use App\Models\Customer;
use App\Notifications\TransactionalMail;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(NotificationTemplateSeeder::class);
});

function seedCustomerInbox(Customer $customer, string $code, int $orderId): string
{
    $customer->notify(new TransactionalMail($code, [
        'order_number' => 'ORD-'.$orderId,
        'grand_total' => '1',
        'customer_name' => $customer->name,
        'tracking_number' => 'T',
        'order_id' => $orderId,
    ]));

    return (string) DB::table('notifications')
        ->where('notifiable_id', $customer->id)
        ->orderByDesc('created_at')
        ->value('id');
}

it('rejects guest notification list', function () {
    $this->getJson('/api/v1/customer/notifications')->assertUnauthorized();
});

it('lists only the authenticated customer notifications newest first', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $other = Customer::factory()->create(['status' => 'active']);
    $olderId = seedCustomerInbox($customer, 'order.placed', 10);
    DB::table('notifications')->where('id', $olderId)->update(['created_at' => now()->subMinute()]);
    $newerId = seedCustomerInbox($customer, 'order.paid', 11);
    seedCustomerInbox($other, 'order.placed', 99);

    $response = $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/notifications')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newerId)
        ->assertJsonPath('data.1.id', $olderId)
        ->assertJsonPath('meta.unread_count', 2)
        ->assertJsonPath('meta.per_page', 15);

    expect($response->json('data.0'))->toHaveKeys(['id', 'code', 'title', 'order_id', 'read_at', 'created_at'])
        ->and($response->json('data.0'))->not->toHaveKey('body');
    $response->assertJsonMissingPath('data.0.body');
});

it('counts unread across pages and honors per_page', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $ids = [];
    for ($i = 1; $i <= 6; $i++) {
        $ids[] = seedCustomerInbox($customer, 'order.placed', $i);
    }
    DB::table('notifications')->where('id', $ids[0])->update(['read_at' => now()]);

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/notifications?per_page=5')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('meta.per_page', 5)
        ->assertJsonPath('meta.total', 6)
        ->assertJsonPath('meta.unread_count', 5);
});

it('marks a notification read and is idempotent', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $id = seedCustomerInbox($customer, 'order.placed', 1);
    seedCustomerInbox($customer, 'order.paid', 2);

    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/notifications/'.$id, ['read' => true])
        ->assertOk()
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.read_at', fn ($value) => is_string($value) && $value !== '');

    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/notifications')
        ->assertOk()
        ->assertJsonPath('meta.unread_count', 1);

    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/notifications/'.$id, ['read' => true])
        ->assertOk();
});

it('rejects unauthenticated mutation and foreign or unknown ids', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $other = Customer::factory()->create(['status' => 'active']);
    $foreignId = seedCustomerInbox($other, 'order.placed', 1);

    $this->patchJson('/api/v1/customer/notifications/'.$foreignId, ['read' => true])->assertUnauthorized();
    $this->postJson('/api/v1/customer/notifications/read-all')->assertUnauthorized();

    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/notifications/'.$foreignId, ['read' => true])
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'NOTIFICATION_NOT_FOUND');
    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/notifications/'.(string) Str::uuid(), ['read' => true])
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'NOTIFICATION_NOT_FOUND');
    $this->actingAs($customer, 'customer')
        ->patchJson('/api/v1/customer/notifications/'.$foreignId, ['read' => false])
        ->assertUnprocessable();
});

it('marks all unread notifications as read', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    seedCustomerInbox($customer, 'order.placed', 1);
    seedCustomerInbox($customer, 'order.paid', 2);

    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/notifications/read-all')
        ->assertOk();
    $this->actingAs($customer, 'customer')
        ->getJson('/api/v1/customer/notifications')
        ->assertOk()
        ->assertJsonPath('meta.unread_count', 0);
});
