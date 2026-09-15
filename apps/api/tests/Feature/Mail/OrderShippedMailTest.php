<?php

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Notifications\TransactionalMail;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(NotificationTemplateSeeder::class);
});

it('sends one order.shipped mail with tracking when shipFull succeeds', function () {
    Notification::fake();
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $customer = Customer::factory()->create();
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 3,
    ]);
    $order = Order::factory()->create(['status' => 'fulfilling', 'customer_id' => $customer->id]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'name' => 'Item',
        'qty' => 3,
        'unit_price' => $variant->price,
        'line_total' => (int) $variant->price * 3,
    ]);
    StockReservation::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'qty' => 3,
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$order->id.'/shipments', [
        'tracking_number' => 'TRACK-1',
    ])->assertCreated();

    Notification::assertSentToTimes($customer, TransactionalMail::class, 1);
    Notification::assertSentTo($customer, TransactionalMail::class, function (TransactionalMail $n) use ($customer) {
        $mail = $n->toMail($customer);

        return $n->code === 'order.shipped'
            && str_contains((string) $mail->viewData['body'], 'TRACK-1');
    });
});

it('does not send order.shipped mail for a guest order', function () {
    Notification::fake();
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 3,
    ]);
    $order = Order::factory()->create(['status' => 'fulfilling']);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'sku' => $variant->sku,
        'name' => 'Item',
        'qty' => 3,
        'unit_price' => $variant->price,
        'line_total' => (int) $variant->price * 3,
    ]);
    StockReservation::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'qty' => 3,
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$order->id.'/shipments', [
        'tracking_number' => 'TRACK-G',
    ])->assertCreated();

    Notification::assertNothingSent();
});
