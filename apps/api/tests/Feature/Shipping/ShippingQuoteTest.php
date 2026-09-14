<?php

use App\Contracts\ShippingGateway;
use App\Models\Customer;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Services\Shipping\FakeGhnGateway;
use Tests\Support\RecordingGhnGateway;

function seedQuoteGeoAndMethods(): array
{
    $province = GeoProvince::query()->create(['code' => '201', 'name' => 'Hà Nội']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => '1484', 'name' => 'Ba Đình']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => '1A0106', 'name' => 'Phúc Xá']);
    $ghn = ShippingMethod::query()->create(['code' => 'ghn', 'name' => 'GHN', 'provider' => 'ghn', 'status' => 'active']);
    $standard = ShippingMethod::query()->create(['code' => 'standard', 'name' => 'Standard', 'provider' => null, 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $standard->id, 'region_code' => null, 'price' => 30000]);

    return ['ghn' => $ghn, 'standard' => $standard, 'rate' => $rate];
}

function seedQuoteCartItem(?int $weightGrams = 500): \App\Models\ProductVariant
{
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => 125000, 'weight_grams' => $weightGrams]);

    return $variant;
}

it('quotes GHN fake services plus nationwide standard for a guest cart', function () {
    $methods = seedQuoteGeoAndMethods();
    $variant = seedQuoteCartItem(500);
    $token = $this->postJson('/api/v1/cart')->assertCreated()->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])
        ->assertCreated();

    $response = $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/shipping/quotes', [
            'province_code' => '201',
            'district_code' => '1484',
            'ward_code' => '1A0106',
        ])
        ->assertOk();

    $data = collect($response->json('data'));
    expect($data)->toHaveCount(3);

    $ghnRows = $data->where('code', 'ghn')->values();
    expect($ghnRows)->toHaveCount(2)
        ->and($ghnRows[0])->toMatchArray([
            'shipping_method_id' => $methods['ghn']->id,
            'code' => 'ghn',
            'name' => 'GHN Chuẩn',
            'fee' => '25000.00',
            'ghn_service_id' => 1,
            'shipping_rate_id' => null,
        ])
        ->and($ghnRows[1])->toMatchArray([
            'shipping_method_id' => $methods['ghn']->id,
            'code' => 'ghn',
            'name' => 'GHN Nhanh',
            'fee' => '32000.00',
            'ghn_service_id' => 2,
            'shipping_rate_id' => null,
        ]);

    $standard = $data->firstWhere('code', 'standard');
    expect($standard)->toMatchArray([
        'shipping_method_id' => $methods['standard']->id,
        'code' => 'standard',
        'name' => 'Standard',
        'fee' => '30000.00',
        'ghn_service_id' => null,
        'shipping_rate_id' => $methods['rate']->id,
    ]);
});

it('quotes the authenticated customer cart without a guest token', function () {
    $methods = seedQuoteGeoAndMethods();
    $variant = seedQuoteCartItem(500);
    $customer = Customer::factory()->create(['status' => 'active']);
    $this->actingAs($customer, 'customer')
        ->postJson('/api/v1/customer/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])
        ->assertCreated();

    $data = collect($this->actingAs($customer, 'customer')
        ->postJson('/api/v1/shipping/quotes', [
            'province_code' => '201',
            'district_code' => '1484',
            'ward_code' => '1A0106',
        ])
        ->assertOk()
        ->json('data'));

    expect($data->where('code', 'ghn'))->toHaveCount(2)
        ->and($data->firstWhere('code', 'standard')['shipping_rate_id'])->toBe($methods['rate']->id);
});

it('returns only standard when GHN is down', function () {
    seedQuoteGeoAndMethods();
    $this->app->instance(ShippingGateway::class, new FakeGhnGateway(false));
    $variant = seedQuoteCartItem(500);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1]);

    $data = collect($this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/shipping/quotes', [
            'province_code' => '201',
            'district_code' => '1484',
            'ward_code' => '1A0106',
        ])
        ->assertOk()
        ->json('data'));

    expect($data)->toHaveCount(1)
        ->and($data[0]['code'])->toBe('standard')
        ->and($data[0]['fee'])->toBe('30000.00');
});

it('rejects invalid geo without calling GHN', function () {
    seedQuoteGeoAndMethods();
    $recorder = new RecordingGhnGateway;
    $this->app->instance(ShippingGateway::class, $recorder);
    $variant = seedQuoteCartItem(500);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1]);

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/shipping/quotes', [
            'province_code' => '201',
            'district_code' => '1484',
            'ward_code' => 'WRONG',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'SHIPPING_QUOTE_INVALID_GEO');

    expect($recorder->quoteCalls)->toBe(0);
});

it('rejects an empty cart', function () {
    seedQuoteGeoAndMethods();
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/shipping/quotes', [
            'province_code' => '201',
            'district_code' => '1484',
            'ward_code' => '1A0106',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'CHECKOUT_INVALID_CART');
});

it('requires a guest cart token', function () {
    seedQuoteGeoAndMethods();

    $this->postJson('/api/v1/shipping/quotes', [
        'province_code' => '201',
        'district_code' => '1484',
        'ward_code' => '1A0106',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.code', 'CART_INVALID_TOKEN');
});

it('uses the default weight when variant weight is missing or not positive', function () {
    config(['commerce.default_weight_grams' => 500]);
    seedQuoteGeoAndMethods();
    $recorder = new RecordingGhnGateway;
    $this->app->instance(ShippingGateway::class, $recorder);
    $variant = seedQuoteCartItem(0);
    $token = $this->postJson('/api/v1/cart')->json('meta.cart_token');
    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])
        ->assertCreated();

    $this->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/shipping/quotes', [
            'province_code' => '201',
            'district_code' => '1484',
            'ward_code' => '1A0106',
        ])
        ->assertOk();

    expect($recorder->quoteCalls)->toBe(1)
        ->and($recorder->lastWeightGrams)->toBe(1000);
});
