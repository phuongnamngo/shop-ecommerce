<?php

use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\StockReservation;
use App\Models\Warehouse;
use App\Services\Payment\FakePaymentGateway;
use App\Support\ErrorCode;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(NotificationTemplateSeeder::class);
});

function persistInboxRows(?int $notifiableId = null): array
{
    $query = DB::table('notifications')->orderBy('created_at');
    if ($notifiableId !== null) {
        $query->where('notifiable_id', $notifiableId);
    }

    return $query->get()->map(function ($row) {
        $data = json_decode((string) $row->data, true, flags: JSON_THROW_ON_ERROR);

        return [
            'code' => $data['code'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'notification_template_id' => $row->notification_template_id,
        ];
    })->all();
}

function seedPersistCheckoutPaymentMethods(): void
{
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    PaymentMethod::query()->updateOrCreate(['code' => 'vnpay'], ['name' => 'VNPay', 'is_active' => true]);
}

it('persists one order.placed inbox row after customer checkout', function () {
    seedPersistCheckoutPaymentMethods();
    $method = ShippingMethod::query()->create(['code' => 'placed-inbox', 'name' => 'Placed', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->create(['code' => 'PLINBOX', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 2, 'qty_reserved' => 0]);
    $customer = Customer::factory()->create(['status' => 'active']);
    $address = CustomerAddress::query()->create([
        'customer_id' => $customer->id,
        'recipient_name' => 'Customer',
        'phone' => '0900000000',
        'province_code' => 'P',
        'district_code' => 'D',
        'ward_code' => 'W',
        'address_line' => 'Road',
        'is_default' => true,
    ]);

    $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/cart/items', [
        'product_variant_id' => $variant->id,
        'qty' => 1,
    ])->assertCreated();
    $response = $this->actingAs($customer, 'customer')->postJson('/api/v1/checkout', [
        'customer_address_id' => $address->id,
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'cod',
    ])->assertCreated();

    $orderId = (int) $response->json('data.id');
    $rows = persistInboxRows($customer->id);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['code'])->toBe('order.placed')
        ->and($rows[0]['order_id'])->toBe($orderId)
        ->and($rows[0]['notification_template_id'])->not->toBeNull();
});

it('does not persist inbox rows for guest checkout', function () {
    seedPersistCheckoutPaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'GPIN', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'GDIN', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'GWIN', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'guest-inbox', 'name' => 'Guest', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    $warehouse = Warehouse::query()->create(['code' => 'GINBOX', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 2, 'qty_reserved' => 0]);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', [
        'product_variant_id' => $variant->id,
        'qty' => 1,
    ])->assertCreated();

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => [
            'recipient_name' => 'A',
            'phone' => '0900000000',
            'province_code' => 'GPIN',
            'district_code' => 'GDIN',
            'ward_code' => 'GWIN',
            'address_line' => 'Road',
        ],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'cod',
    ])->assertCreated();

    expect(persistInboxRows())->toHaveCount(0);
});

it('persists one order.paid row and not again on a second paid patch', function () {
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
    $this->actingAs($admin, 'admin')
        ->patchJson('/api/v1/admin/orders/'.$order->id.'/status', ['status' => 'paid'])
        ->assertConflict();

    $rows = persistInboxRows($customer->id);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['code'])->toBe('order.paid')
        ->and($rows[0]['order_id'])->toBe($order->id)
        ->and($rows[0]['notification_template_id'])->not->toBeNull();
});

it('persists one order.paid row for VNPay IPN and not again on retry', function () {
    $customer = Customer::factory()->create();
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret']);
    $method = PaymentMethod::query()->create(['code' => 'vnpay', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'number' => 'ORD-PAIDINBOX',
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
        'vnp_TxnRef' => 'ORD-PAIDINBOX',
        'vnp_TransactionNo' => 'VN123',
        'vnp_TransactionDate' => '20260914120000',
    ];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);

    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))
        ->assertOk()
        ->assertSee('Confirm Success');
    $this->get('/api/v1/payments/vnpay/ipn?'.http_build_query($payload))->assertOk();

    $rows = persistInboxRows($customer->id);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['code'])->toBe('order.paid')
        ->and($rows[0]['order_id'])->toBe($order->id)
        ->and($rows[0]['notification_template_id'])->not->toBeNull();
});

it('persists one order.shipped row and not again on a second ship', function () {
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
    $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/orders/'.$order->id.'/shipments', [
        'tracking_number' => 'TRACK-2',
    ])->assertConflict()
        ->assertJsonPath('errors.0.code', ErrorCode::SHIPMENT_INVALID_STATUS);

    $rows = persistInboxRows($customer->id);
    expect($rows)->toHaveCount(1)
        ->and($rows[0]['code'])->toBe('order.shipped')
        ->and($rows[0]['order_id'])->toBe($order->id)
        ->and($rows[0]['notification_template_id'])->not->toBeNull();
});
