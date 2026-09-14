<?php

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Refund;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function ledgerTxn(
    string $provider,
    string $status = 'succeeded',
    int $amount = 100000,
    ?string $number = null,
    ?string $createdAt = null,
): PaymentTransaction {
    $method = PaymentMethod::query()->firstOrCreate(
        ['code' => $provider],
        ['name' => strtoupper($provider), 'is_active' => true],
    );
    $order = Order::factory()->create([
        'status' => 'paid',
        'number' => $number ?? 'ORD-'.strtoupper(Str::random(6)),
        'grand_total' => $amount,
    ]);
    $txn = PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => $provider,
        'idempotency_key' => (string) Str::uuid(),
        'amount' => $amount,
        'status' => $status,
        'provider_txn_id' => $provider === 'vnpay' ? 'VN-'.$order->id : null,
    ]);
    if ($createdAt !== null) {
        $txn->forceFill(['created_at' => $createdAt])->save();
    }

    return $txn->refresh();
}

it('lists payment transactions with pagination and full-set totals', function () {
    $admin = catalogAdmin('admin');
    $cod = ledgerTxn('cod', amount: 50000);
    $vnpay = ledgerTxn('vnpay', amount: 100000);
    Refund::factory()->create([
        'payment_transaction_id' => $vnpay->id,
        'amount' => $vnpay->amount,
        'status' => Refund::STATUS_PENDING,
        'reason' => 'open',
    ]);

    $page = $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?per_page=1&page=1')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonCount(1, 'data');

    expect($page->json('meta.sum_succeeded'))->toBe('150000.00')
        ->and($page->json('meta.sum_refunded'))->toBe('0.00')
        ->and($page->json('meta.count_pending'))->toBe(1)
        ->and($page->json('meta.count_failed'))->toBe(0);

    $vnpayList = $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?provider=vnpay')
        ->assertOk()
        ->assertJsonCount(1, 'data');
    expect($vnpayList->json('meta.sum_succeeded'))->toBe('100000.00')
        ->and($vnpayList->json('data.0.id'))->toBe($vnpay->id)
        ->and($vnpayList->json('data.0.refund.status'))->toBe('pending');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?status=succeeded&q='.$cod->order->number)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.provider', 'cod');
});

it('filters by refund_status, date range, and refund precedence', function () {
    $admin = catalogAdmin('admin');
    $pending = ledgerTxn('vnpay', amount: 10000, createdAt: '2026-09-01 10:00:00');
    Refund::factory()->create([
        'payment_transaction_id' => $pending->id,
        'amount' => 10000,
        'status' => Refund::STATUS_PENDING,
        'reason' => 'p',
    ]);
    $failed = ledgerTxn('vnpay', amount: 20000, createdAt: '2026-09-10 10:00:00');
    Refund::factory()->create([
        'payment_transaction_id' => $failed->id,
        'amount' => 20000,
        'status' => Refund::STATUS_FAILED,
        'reason' => 'f',
    ]);
    $done = ledgerTxn('cod', amount: 30000, createdAt: '2026-09-12 10:00:00');
    Refund::factory()->create([
        'payment_transaction_id' => $done->id,
        'amount' => 30000,
        'status' => Refund::STATUS_SUCCEEDED,
        'reason' => 's',
    ]);
    $plain = ledgerTxn('cod', amount: 40000, createdAt: '2026-09-20 10:00:00');
    Refund::factory()->create([
        'payment_transaction_id' => $plain->id,
        'amount' => 40000,
        'status' => Refund::STATUS_REJECTED,
        'reason' => 'r',
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?refund_status=pending')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $pending->id);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?refund_status=failed')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $failed->id);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?refund_status=succeeded')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $done->id)
        ->assertJsonPath('data.0.refund.status', 'succeeded');

    $none = $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?q='.$plain->order->number)
        ->assertOk()
        ->assertJsonCount(1, 'data');
    expect($none->json('data.0.refund'))->toBeNull();

    $ranged = $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions?from=2026-09-09&to=2026-09-13')
        ->assertOk();
    expect(collect($ranged->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe([$failed->id, $done->id]);
});

it('shows a payment transaction and returns PAYMENT_NOT_FOUND for unknown ids', function () {
    $admin = catalogAdmin('admin');
    $txn = ledgerTxn('cod');

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions/'.$txn->id)
        ->assertOk()
        ->assertJsonPath('data.id', $txn->id)
        ->assertJsonPath('data.order_id', $txn->order_id);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/payments/transactions/999999')
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', 'PAYMENT_NOT_FOUND');
});

it('lets staff view the ledger and includes payment on admin order show', function () {
    $staff = catalogAdmin('staff');
    $txn = ledgerTxn('vnpay');
    Refund::factory()->create([
        'payment_transaction_id' => $txn->id,
        'amount' => $txn->amount,
        'status' => Refund::STATUS_PENDING,
        'reason' => 'hold',
    ]);

    $this->actingAs($staff, 'admin')
        ->getJson('/api/v1/admin/payments/transactions')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($staff, 'admin')
        ->getJson('/api/v1/admin/payments/transactions/'.$txn->id)
        ->assertOk();

    $this->actingAs($staff, 'admin')
        ->getJson('/api/v1/admin/orders/'.$txn->order_id)
        ->assertOk()
        ->assertJsonPath('data.payment.id', $txn->id)
        ->assertJsonPath('data.payment.provider', 'vnpay')
        ->assertJsonPath('data.refunds.0.status', 'pending');
});
