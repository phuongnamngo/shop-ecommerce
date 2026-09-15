<?php

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\NotificationTemplate;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use App\Notifications\TransactionalMail;
use Database\Seeders\NotificationTemplateSeeder;
use Illuminate\Support\Facades\Notification;

function seedCheckoutPaymentMethods(): void
{
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    PaymentMethod::query()->updateOrCreate(['code' => 'vnpay'], ['name' => 'VNPay', 'is_active' => true]);
}

it('sends order.placed mail after a customer checkout', function () {
    $this->seed(NotificationTemplateSeeder::class);
    Notification::fake();
    seedCheckoutPaymentMethods();
    $method = ShippingMethod::query()->create(['code' => 'placed-mail', 'name' => 'Placed', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->create(['code' => 'PLACED', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
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

    $order = Order::query()->findOrFail($response->json('data.id'));
    Notification::assertSentTo($customer, TransactionalMail::class, function (TransactionalMail $n) use ($customer, $order) {
        $mail = $n->toMail($customer);

        return $n->code === 'order.placed'
            && str_contains((string) $mail->viewData['body'], $order->number)
            && str_contains((string) $mail->viewData['body'], (string) $order->grand_total);
    });
});

it('does not send order.placed mail for guest checkout', function () {
    $this->seed(NotificationTemplateSeeder::class);
    Notification::fake();
    seedCheckoutPaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'GP', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'GD', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'GW', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'guest-placed', 'name' => 'Guest', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    $warehouse = Warehouse::query()->create(['code' => 'GUESTPL', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
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
            'province_code' => 'GP',
            'district_code' => 'GD',
            'ward_code' => 'GW',
            'address_line' => 'Road',
        ],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'cod',
    ])->assertCreated();

    Notification::assertNothingSent();
});

it('still creates the order when the placed template is missing', function () {
    Notification::fake();
    seedCheckoutPaymentMethods();
    $method = ShippingMethod::query()->create(['code' => 'missing-placed', 'name' => 'Missing', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->create(['code' => 'MISSPl', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
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
    $this->seed(NotificationTemplateSeeder::class);
    NotificationTemplate::query()->where('code', 'order.placed')->delete();

    $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/cart/items', [
        'product_variant_id' => $variant->id,
        'qty' => 1,
    ])->assertCreated();
    $this->actingAs($customer, 'customer')->postJson('/api/v1/checkout', [
        'customer_address_id' => $address->id,
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'cod',
    ])->assertCreated();

    Notification::assertNothingSent();
});
