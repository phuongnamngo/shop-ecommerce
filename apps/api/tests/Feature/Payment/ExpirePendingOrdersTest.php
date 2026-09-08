<?php

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

it('expires pending orders with elapsed reservation TTL', function () {
    $method = PaymentMethod::query()->create(['code' => 'cod', 'name' => 'COD', 'is_active' => true]);
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $stock = StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 5,
        'qty_reserved' => 2,
    ]);
    $order = Order::factory()->create(['status' => 'pending']);
    StockReservation::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'qty' => 2,
        'status' => 'active',
        'expires_at' => now()->subMinute(),
    ]);
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'cod',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => $order->grand_total,
        'status' => 'pending',
    ]);

    Artisan::call('commerce:expire-pending-orders');

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($stock->refresh()->qty_reserved)->toBe(0)
        ->and($stock->qty_on_hand)->toBe(5);
    $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'released']);
    $this->assertDatabaseHas('payment_transactions', ['order_id' => $order->id, 'status' => 'expired']);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'to_status' => 'cancelled',
        'note' => 'system:ttl_expired',
        'changed_by_admin_id' => null,
    ]);
});
