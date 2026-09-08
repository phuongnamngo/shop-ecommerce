<?php

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Discount;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\StockReservation;
use App\Models\Warehouse;
use Illuminate\Support\Str;

function ensurePaymentMethods(): void
{
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    PaymentMethod::query()->updateOrCreate(['code' => 'vnpay'], ['name' => 'VNPay', 'is_active' => true]);
    PaymentMethod::query()->updateOrCreate(['code' => 'momo'], ['name' => 'MoMo', 'is_active' => false]);
}

it('requires a shipping address and shipping selection for checkout', function () {
    $token = $this->postJson('/api/v1/cart')->assertCreated()->json('meta.cart_token');

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/checkout', [])
        ->assertUnprocessable();
});

it('rejects checkout with both customer and inline address sources', function () {
    $customer = Customer::factory()->create(['status' => 'active']);
    $address = CustomerAddress::query()->create([
        'customer_id' => $customer->id,
        'recipient_name' => 'Customer',
        'phone' => '0900000000',
        'province_code' => 'P',
        'district_code' => 'D',
        'ward_code' => 'W',
        'address_line' => 'Road',
    ]);

    $this->actingAs($customer, 'customer')->postJson('/api/v1/checkout', [
        'customer_address_id' => $address->id,
        'shipping_address' => ['recipient_name' => 'Other', 'phone' => '0900000001', 'province_code' => 'P', 'district_code' => 'D', 'ward_code' => 'W', 'address_line' => 'Other road'],
        'shipping_method_id' => 1,
        'shipping_rate_id' => 1,
        'payment_method_code' => 'cod',
    ])->assertUnprocessable()->assertJsonFragment(['field' => 'customer_address_id']);
});

it('requires an active guest cart token', function () {
    ensurePaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'PX', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DX', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WX', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'express', 'name' => 'Express', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $this->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PX', 'district_code' => 'DX', 'ward_code' => 'WX', 'address_line' => 'Road'],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod',
    ])->assertUnprocessable()->assertJsonPath('errors.0.code', 'CART_INVALID_TOKEN');
});

it('rejects a shipping rate from another method and invalid geo relationships', function () {
    ensurePaymentMethods();
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $province = GeoProvince::query()->create(['code' => 'P1', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'D1', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'W1', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'standard', 'name' => 'Standard', 'status' => 'active']);
    $other = ShippingMethod::query()->create(['code' => 'other', 'name' => 'Other', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $other->id, 'price' => 10000]);
    $payload = ['shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'P1', 'district_code' => 'D1', 'ward_code' => 'W1', 'address_line' => '1 Road'], 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod'];

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', $payload)->assertUnprocessable()->assertJsonPath('errors.0.code', 'CHECKOUT_INVALID_CART');
    $payload['shipping_rate_id'] = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000])->id;
    $payload['shipping_address']['district_code'] = 'wrong';
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', $payload)->assertUnprocessable();
});

it('recomputes totals and creates an atomic order reservation', function () {
    ensurePaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'PC', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DC', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WC', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'checkout', 'name' => 'Checkout', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'min_order_amount' => 100000, 'price' => 30000]);
    $warehouse = Warehouse::query()->create(['code' => 'DEFAULT', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => 125000]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 5, 'qty_reserved' => 0]);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])->assertCreated();
    $variant->update(['price' => 150000]);

    $response = $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PC', 'district_code' => 'DC', 'ward_code' => 'WC', 'address_line' => 'Road'],
        'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod',
    ])->assertCreated()->assertJsonPath('data.subtotal', '300000.00')->assertJsonPath('data.tax_total', '0.00')->assertJsonPath('data.grand_total', '330000.00')->assertJsonPath('data.next_action', 'payment_pending');

    $this->assertDatabaseHas('stock_items', ['product_variant_id' => $variant->id, 'qty_reserved' => 2]);
    $this->assertDatabaseHas('stock_reservations', ['order_id' => $response->json('data.id'), 'qty' => 2, 'status' => 'active']);
    expect(StockReservation::query()->where('order_id', $response->json('data.id'))->value('expires_at'))->not->toBeNull();
    $this->assertDatabaseHas('payment_transactions', ['order_id' => $response->json('data.id'), 'provider' => 'cod', 'status' => 'pending']);
    $this->assertDatabaseHas('carts', ['session_id' => $token, 'status' => 'converted']);
    expect($response->json('data.payment.provider'))->toBe('cod');
    $this->assertDatabaseHas('order_status_histories', ['order_id' => $response->json('data.id'), 'to_status' => 'pending']);
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PC', 'district_code' => 'DC', 'ward_code' => 'WC', 'address_line' => 'Road'],
        'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod',
    ])->assertUnprocessable()->assertJsonPath('errors.0.code', 'CART_INVALID_TOKEN');
});

it('rolls back checkout when stock is insufficient', function () {
    ensurePaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'PR', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DR', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WR', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'rollback', 'name' => 'Rollback', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    $warehouse = Warehouse::query()->create(['code' => 'ROLLBACK', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 1, 'qty_reserved' => 0]);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2]);

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', ['shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PR', 'district_code' => 'DR', 'ward_code' => 'WR', 'address_line' => 'Road'], 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod'])->assertConflict()->assertJsonPath('errors.0.code', 'INVENTORY_INSUFFICIENT_STOCK');
    $this->assertDatabaseCount('orders', 0);
    $this->assertDatabaseHas('stock_items', ['product_variant_id' => $variant->id, 'qty_reserved' => 0]);
});

it('applies the documented percentage coupon contract', function () {
    ensurePaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'PP', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DP', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WP', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'coupon', 'name' => 'Coupon', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->create(['code' => 'COUPON', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 3, 'qty_reserved' => 0]);
    $discount = Discount::query()->create(['code' => (string) Str::ulid(), 'name' => 'Ten percent', 'type' => 'percentage', 'value' => 10, 'status' => 'active']);
    $discount->rules()->create(['conditions' => ['min_subtotal' => 50000]]);
    Coupon::query()->create(['code' => 'TEN', 'discount_id' => $discount->id, 'max_uses' => 2, 'used_count' => 0, 'status' => 'active']);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1]);

    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', ['shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PP', 'district_code' => 'DP', 'ward_code' => 'WP', 'address_line' => 'Road'], 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod', 'coupon_code' => 'TEN'])->assertCreated()->assertJsonPath('data.discount_total', '10000.00')->assertJsonPath('data.grand_total', '100000.00');
    $this->assertDatabaseHas('coupons', ['code' => 'TEN', 'used_count' => 1]);
    $this->assertDatabaseCount('coupon_redemptions', 1);
    Coupon::query()->where('code', 'TEN')->update(['max_uses' => 1]);
    $secondToken = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $secondToken)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1]);
    $this->withHeader('X-Cart-Token', $secondToken)->postJson('/api/v1/checkout', ['shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PP', 'district_code' => 'DP', 'ward_code' => 'WP', 'address_line' => 'Road'], 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod', 'coupon_code' => 'TEN'])->assertUnprocessable()->assertJsonPath('errors.0.code', 'COUPON_INVALID');
});

it('checks out the authenticated customer cart with an owned address', function () {
    ensurePaymentMethods();
    $method = ShippingMethod::query()->create(['code' => 'customer-checkout', 'name' => 'Customer', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->create(['code' => 'CUSTOMER', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 2, 'qty_reserved' => 0]);
    $customer = Customer::factory()->create(['status' => 'active']);
    $address = CustomerAddress::query()->create(['customer_id' => $customer->id, 'recipient_name' => 'Customer', 'phone' => '0900000000', 'province_code' => 'P', 'district_code' => 'D', 'ward_code' => 'W', 'address_line' => 'Road', 'is_default' => true]);

    $this->actingAs($customer, 'customer')->postJson('/api/v1/customer/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])->assertCreated();
    $this->actingAs($customer, 'customer')->postJson('/api/v1/checkout', ['customer_address_id' => $address->id, 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod'])->assertCreated()->assertJsonPath('data.status', 'pending');
    $this->assertDatabaseHas('orders', ['customer_id' => $customer->id]);
});

it('rejects checkout for banned or inactive authenticated customers', function () {
    ensurePaymentMethods();
    $method = ShippingMethod::query()->create(['code' => 'banned-checkout', 'name' => 'Banned', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    $warehouse = Warehouse::query()->create(['code' => 'BANNED', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 2, 'qty_reserved' => 0]);
    $banned = Customer::factory()->create(['status' => 'banned']);
    $inactive = Customer::factory()->create(['status' => 'inactive']);
    $payload = [
        'shipping_address' => [
            'recipient_name' => 'A',
            'phone' => '0900000000',
            'province_code' => 'P',
            'district_code' => 'D',
            'ward_code' => 'W',
            'address_line' => 'Road',
        ],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod',
    ];

    $this->actingAs($banned, 'customer')->postJson('/api/v1/checkout', $payload)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'AUTH_ACCOUNT_BANNED');
    $this->actingAs($inactive, 'customer')->postJson('/api/v1/checkout', $payload)
        ->assertForbidden()
        ->assertJsonPath('errors.0.code', 'AUTH_ACCOUNT_INACTIVE');
});

it('checks out with VNPay and returns a redirect payment action', function () {
    ensurePaymentMethods();
    $province = GeoProvince::query()->create(['code' => 'PV', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DV', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WV', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'vnpay-ship', 'name' => 'Ship', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    $warehouse = Warehouse::query()->create(['code' => 'VNPAY', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 2, 'qty_reserved' => 0]);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1]);
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PV', 'district_code' => 'DV', 'ward_code' => 'WV', 'address_line' => 'Road'],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'vnpay',
    ])->assertCreated()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.next_action', 'redirect_payment')
        ->assertJsonPath('data.payment.provider', 'vnpay');
    expect($this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [])->json())->toBeArray();
    $this->assertDatabaseHas('payment_transactions', ['provider' => 'vnpay', 'status' => 'pending']);
});

it('rejects inactive payment methods at checkout', function () {
    ensurePaymentMethods();
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'P', 'district_code' => 'D', 'ward_code' => 'W', 'address_line' => 'Road'],
        'shipping_method_id' => 1,
        'shipping_rate_id' => 1,
        'payment_method_code' => 'momo',
    ])->assertUnprocessable();
});
