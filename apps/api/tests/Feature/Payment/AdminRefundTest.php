<?php

use App\Contracts\PaymentGateway;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderShipment;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Refund;
use App\Models\StockItem;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Services\Payment\FakePaymentGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->app->instance(PaymentGateway::class, new FakePaymentGateway);
});

/**
 * @return array{admin: AdminUser, order: Order, txn: PaymentTransaction, stock: StockItem}
 */
function paidRefundableOrder(
    string $provider = 'cod',
    ?string $providerTxnId = null,
    string $orderStatus = 'paid',
    string $txnStatus = 'succeeded',
    int $amount = 100000,
    ?int $grandTotal = null,
): array {
    $admin = AdminUser::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $warehouse = Warehouse::factory()->create(['status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $stock = StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 5,
        'qty_reserved' => 2,
    ]);
    $order = Order::factory()->create([
        'status' => $orderStatus,
        'grand_total' => $grandTotal ?? $amount,
    ]);
    StockReservation::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'order_id' => $order->id,
        'qty' => 2,
        'status' => 'active',
    ]);
    $method = PaymentMethod::query()->firstOrCreate(
        ['code' => $provider],
        ['name' => strtoupper($provider), 'is_active' => true],
    );
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => $provider,
        'provider_txn_id' => $providerTxnId,
        'idempotency_key' => (string) Str::uuid(),
        'amount' => $amount,
        'status' => $txnStatus,
        'payload' => $providerTxnId ? ['vnp_TransactionDate' => '20260914120000'] : null,
    ]);

    return compact('admin', 'order', 'txn', 'stock');
}

it('approves a COD refund, cancels the order, and releases reservations', function () {
    ['admin' => $admin, 'order' => $order, 'txn' => $txn, 'stock' => $stock] = paidRefundableOrder();

    $create = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Customer cancelled before ship'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');
    $refundId = $create->json('data.id');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertOk()
        ->assertJsonPath('data.status', 'succeeded');

    expect($order->refresh()->status)->toBe('cancelled')
        ->and($txn->refresh()->status)->toBe('succeeded')
        ->and($stock->refresh()->qty_reserved)->toBe(0);
    $this->assertDatabaseHas('stock_reservations', ['order_id' => $order->id, 'status' => 'released']);
    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'to_status' => 'cancelled',
        'changed_by_admin_id' => $admin->id,
    ]);
});

it('refunds VNPay with provider_txn_id through the gateway and stores provider_refund_id', function () {
    ['admin' => $admin, 'order' => $order] = paidRefundableOrder('vnpay', 'VN-99');

    $refundId = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Full refund'])
        ->assertCreated()
        ->json('data.id');

    $approved = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertOk()
        ->assertJsonPath('data.status', 'succeeded');
    expect($approved->json('data.provider_refund_id'))->toStartWith('fake-')
        ->and($order->refresh()->status)->toBe('cancelled');
});

it('persists failed refunds on gateway error, keeps the order, and retries with the same idempotency key', function () {
    $this->app->instance(PaymentGateway::class, new FakePaymentGateway(false));
    ['admin' => $admin, 'order' => $order] = paidRefundableOrder('vnpay', 'VN-FAIL');

    $created = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Retry me'])
        ->assertCreated();
    $refundId = $created->json('data.id');
    $key = $created->json('data.idempotency_key');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'REFUND_GATEWAY_FAILED');

    $refund = Refund::query()->findOrFail($refundId);
    expect($order->refresh()->status)->toBe('paid')
        ->and($refund->status)->toBe('failed')
        ->and($refund->idempotency_key)->toBe($key);

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'REFUND_INVALID_STATUS');

    $this->app->instance(PaymentGateway::class, new FakePaymentGateway(true));
    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/retry')
        ->assertOk()
        ->assertJsonPath('data.status', 'succeeded')
        ->assertJsonPath('data.idempotency_key', $key);

    expect($order->refresh()->status)->toBe('cancelled');
});

it('ledgers a VNPay refund without provider_txn_id without calling the gateway', function () {
    $this->app->instance(PaymentGateway::class, new FakePaymentGateway(false));
    ['admin' => $admin, 'order' => $order] = paidRefundableOrder('vnpay', null);

    $refundId = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Manual VNPay'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertOk()
        ->assertJsonPath('data.status', 'succeeded')
        ->assertJsonPath('data.provider_refund_id', null);

    expect($order->refresh()->status)->toBe('cancelled');
});

it('rejects a refund then allows creating another', function () {
    ['admin' => $admin, 'order' => $order] = paidRefundableOrder();

    $refundId = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'First try'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/reject')
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Second try'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');
});

it('rejects ineligible refunds', function (string $setup) {
    ['admin' => $admin, 'order' => $order] = match ($setup) {
        'pending' => paidRefundableOrder(orderStatus: 'pending'),
        'shipped' => paidRefundableOrder(orderStatus: 'shipped'),
        'txn-pending' => paidRefundableOrder(txnStatus: 'pending'),
        'momo' => paidRefundableOrder('momo', 'MOMO-1'),
        'amount-mismatch' => paidRefundableOrder(amount: 100000, grandTotal: 200000),
        default => paidRefundableOrder(),
    };

    if ($setup === 'no-txn') {
        $admin = AdminUser::factory()->create(['status' => 'active']);
        $admin->assignRole('admin');
        $order = Order::factory()->create(['status' => 'paid', 'grand_total' => 100000]);
    }

    if ($setup === 'has-shipment') {
        OrderShipment::query()->create([
            'order_id' => $order->id,
            'tracking_number' => 'SHIP-1',
            'status' => 'created',
        ])->delete();
    }

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Nope'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'REFUND_NOT_ELIGIBLE');
})->with(['pending', 'shipped', 'no-txn', 'txn-pending', 'has-shipment', 'momo', 'amount-mismatch']);

it('blocks a second open refund and a second succeeded refund', function () {
    ['admin' => $admin, 'order' => $order] = paidRefundableOrder();

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Open'])
        ->assertCreated();

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Duplicate'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'REFUND_ALREADY_OPEN');

    $other = paidRefundableOrder();
    $refundId = $this->actingAs($other['admin'], 'admin')
        ->postJson('/api/v1/admin/orders/'.$other['order']->id.'/refunds', ['reason' => 'Done'])
        ->assertCreated()
        ->json('data.id');
    $this->actingAs($other['admin'], 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/approve')
        ->assertOk();
    $this->actingAs($other['admin'], 'admin')
        ->postJson('/api/v1/admin/orders/'.$other['order']->id.'/refunds', ['reason' => 'Again'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'REFUND_ALREADY_SUCCEEDED');
});

it('lets staff view the order but forbids creating refunds', function () {
    ['order' => $order] = paidRefundableOrder();
    $staff = catalogAdmin('staff');

    $this->actingAs($staff, 'admin')
        ->getJson('/api/v1/admin/orders/'.$order->id)
        ->assertOk();

    $this->actingAs($staff, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Staff'])
        ->assertForbidden();
});

it('blocks fulfilling and shipping while a refund is open, then allows fulfilling after reject', function () {
    ['admin' => $admin, 'order' => $order] = paidRefundableOrder();

    $refundId = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Hold ship'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'fulfilling'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'ORDER_REFUND_IN_PROGRESS');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/refunds/'.$refundId.'/reject')
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'fulfilling'])
        ->assertOk()
        ->assertJsonPath('data.status', 'fulfilling');

    $openId = $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/refunds', ['reason' => 'Hold again'])
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/orders/'.$order->id.'/shipments', ['tracking_number' => 'T-1'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', 'ORDER_REFUND_IN_PROGRESS');

    expect(Refund::query()->findOrFail($openId)->status)->toBe('pending');
});
