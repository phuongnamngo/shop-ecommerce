<?php

use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use App\Services\Payment\FakePaymentGateway;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

function ensureLookupPaymentMethods(): void
{
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    PaymentMethod::query()->updateOrCreate(['code' => 'vnpay'], ['name' => 'VNPay', 'is_active' => true]);
}

function guestCodCheckout(): array
{
    ensureLookupPaymentMethods();
    $suffix = Str::lower(Str::random(6));
    $province = GeoProvince::query()->create(['code' => 'G'.$suffix, 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'D'.$suffix, 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'W'.$suffix, 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'lookup-'.$suffix, 'name' => 'Ship', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->firstOrCreate(
        ['is_default' => true, 'status' => 'active'],
        ['code' => 'LU-'.$suffix, 'name' => 'Default'],
    );
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => 100000]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 5, 'qty_reserved' => 0]);
    $token = test()->postJson('/api/v1/cart')->json('meta.cart_token');
    test()->withHeader('X-Cart-Token', $token)->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1]);

    $response = test()->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => [
            'recipient_name' => 'A',
            'phone' => '0900000000',
            'province_code' => $province->code,
            'district_code' => $district->code,
            'ward_code' => 'W'.$suffix,
            'address_line' => 'Road',
        ],
        'shipping_method_id' => $method->id,
        'shipping_rate_id' => $rate->id,
        'payment_method_code' => 'cod',
    ]);

    return ['response' => $response, 'cart_token' => $token];
}

it('issues a guest lookup token and serves the order by hash', function () {
    $created = guestCodCheckout()['response']->assertCreated();
    $plain = $created->json('data.lookup_token');
    expect($plain)->toBeString()->and(strlen($plain))->toBe(64);
    $order = Order::query()->where('number', $created->json('data.number'))->firstOrFail();
    expect($order->guest_lookup_token_hash)->toBe(hash('sha256', $plain))
        ->and($order->guest_lookup_token_expires_at?->greaterThan(now()->addDays(29)))->toBeTrue();

    $first = test()->getJson('/api/v1/orders/lookup?token='.$plain)
        ->assertOk()
        ->assertJsonPath('data.number', $created->json('data.number'))
        ->assertJsonPath('data.grand_total', $created->json('data.grand_total'))
        ->assertJsonMissingPath('data.lookup_token');
    expect($first->json('data'))->not->toHaveKey('guest_lookup_token_hash')
        ->and($first->json('data'))->not->toHaveKey('guest_lookup_token_cipher');

    test()->getJson('/api/v1/orders/lookup?token='.$plain)->assertOk();
});

it('returns the same not-found envelope for missing invalid and expired tokens', function () {
    $created = guestCodCheckout()['response']->assertCreated();
    $plain = $created->json('data.lookup_token');
    Order::query()->where('number', $created->json('data.number'))->update([
        'guest_lookup_token_expires_at' => now()->subMinute(),
    ]);

    foreach (['/api/v1/orders/lookup', '/api/v1/orders/lookup?token=', '/api/v1/orders/lookup?token=deadbeef'] as $url) {
        test()->getJson($url)
            ->assertNotFound()
            ->assertJsonPath('errors.0.code', ErrorCode::ORDER_LOOKUP_INVALID)
            ->assertJsonPath('errors.0.message', 'Order was not found.');
    }

    test()->getJson('/api/v1/orders/lookup?token='.$plain)
        ->assertNotFound()
        ->assertJsonPath('errors.0.code', ErrorCode::ORDER_LOOKUP_INVALID);
});

it('does not open another guest order with a foreign lookup token', function () {
    $first = guestCodCheckout()['response']->assertCreated();
    $second = guestCodCheckout()['response']->assertCreated();

    test()->getJson('/api/v1/orders/lookup?token='.$first->json('data.lookup_token'))
        ->assertOk()
        ->assertJsonPath('data.number', $first->json('data.number'));
    expect(test()->getJson('/api/v1/orders/lookup?token='.$first->json('data.lookup_token'))->json('data.number'))
        ->not->toBe($second->json('data.number'));
});

it('attaches lookup token on verified VNPay return only', function () {
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret', 'app.frontend_url' => 'http://localhost:3000']);
    $plain = bin2hex(random_bytes(32));
    $method = PaymentMethod::query()->create(['code' => 'vnpay-ret', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create([
        'status' => 'pending',
        'number' => 'ORD-RET1',
        'grand_total' => 100000,
        'guest_lookup_token_hash' => hash('sha256', $plain),
        'guest_lookup_token_cipher' => Crypt::encryptString($plain),
        'guest_lookup_token_expires_at' => now()->addDays(30),
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
    $payload = ['vnp_ResponseCode' => '00', 'vnp_TxnRef' => 'ORD-RET1', 'vnp_TransactionNo' => 'VN1'];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);

    $ok = $this->get('/api/v1/payments/vnpay/return?'.http_build_query($payload));
    $ok->assertRedirect();
    expect($ok->headers->get('Location'))->toContain('token='.$plain)->toContain('status=paid');

    $bad = $payload;
    $bad['vnp_SecureHash'] = 'nope';
    $fail = $this->get('/api/v1/payments/vnpay/return?'.http_build_query($bad));
    $fail->assertRedirect();
    expect($fail->headers->get('Location'))->not->toContain('token=')->toContain('ORD-RET1');
});

it('omits token on verified return when cipher is missing or undecryptable', function () {
    config(['commerce.vnpay.hash_secret' => 'testing-vnpay-secret', 'app.frontend_url' => 'http://localhost:3000']);
    $method = PaymentMethod::query()->create(['code' => 'vnpay-nocipher', 'name' => 'VNPay', 'is_active' => true]);
    $order = Order::factory()->create(['status' => 'pending', 'number' => 'ORD-RET2', 'grand_total' => 10000]);
    PaymentTransaction::query()->create([
        'order_id' => $order->id,
        'payment_method_id' => $method->id,
        'provider' => 'vnpay',
        'idempotency_key' => (string) Str::uuid(),
        'amount' => 10000,
        'status' => 'pending',
    ]);
    $gateway = new FakePaymentGateway;
    $payload = ['vnp_ResponseCode' => '00', 'vnp_TxnRef' => 'ORD-RET2', 'vnp_TransactionNo' => 'VN2'];
    $payload['vnp_SecureHash'] = $gateway->hash($payload);
    $none = $this->get('/api/v1/payments/vnpay/return?'.http_build_query($payload));
    expect($none->headers->get('Location'))->not->toContain('token=');

    $order->update(['guest_lookup_token_cipher' => 'not-a-valid-payload']);
    $payload2 = ['vnp_ResponseCode' => '00', 'vnp_TxnRef' => 'ORD-RET2', 'vnp_TransactionNo' => 'VN3'];
    $payload2['vnp_SecureHash'] = $gateway->hash($payload2);
    $broken = $this->get('/api/v1/payments/vnpay/return?'.http_build_query($payload2));
    $broken->assertRedirect();
    expect($broken->headers->get('Location'))->not->toContain('token=');
});

it('rejects converted guest cart tokens', function () {
    $cartToken = guestCodCheckout()['cart_token'];
    $this->withHeader('X-Cart-Token', $cartToken)
        ->getJson('/api/v1/cart')
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'CART_INVALID_TOKEN');
});
