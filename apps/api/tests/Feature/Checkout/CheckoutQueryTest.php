<?php

use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('does not query products per checkout line or bump the catalog cache', function () {
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    $province = GeoProvince::query()->create(['code' => 'PC', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'DC', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'WC', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'checkout-query', 'name' => 'Checkout', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'min_order_amount' => 100000, 'price' => 30000]);
    $warehouse = Warehouse::query()->create(['code' => 'DEFAULT', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $first = $product->variants()->firstOrFail();
    $first->update(['status' => 'active', 'price' => 150000]);
    $second = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'status' => 'active',
        'price' => 160000,
        'is_default' => false,
    ]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $first->id, 'qty_on_hand' => 5, 'qty_reserved' => 0]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $second->id, 'qty_on_hand' => 5, 'qty_reserved' => 0]);

    $oneLine = checkoutRelationQueries($method->id, $rate->id, [$first->id]);
    $twoLines = checkoutRelationQueries($method->id, $rate->id, [$first->id, $second->id]);

    expect($twoLines)->toBeLessThanOrEqual($oneLine + 1);
});

/**
 * @param  list<int>  $variantIds
 */
function checkoutRelationQueries(int $methodId, int $rateId, array $variantIds): int
{
    Cache::put('catalog.public.version', 4);
    $token = test()->postJson('/api/v1/cart')->assertCreated()->json('meta.cart_token');
    foreach ($variantIds as $variantId) {
        test()->withHeader('X-Cart-Token', $token)
            ->postJson('/api/v1/cart/items', ['product_variant_id' => $variantId, 'qty' => 1])
            ->assertCreated();
    }

    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->withHeader('X-Cart-Token', $token)->postJson('/api/v1/checkout', [
        'shipping_address' => ['recipient_name' => 'A', 'phone' => '0900000000', 'province_code' => 'PC', 'district_code' => 'DC', 'ward_code' => 'WC', 'address_line' => 'Road'],
        'shipping_method_id' => $methodId,
        'shipping_rate_id' => $rateId,
        'payment_method_code' => 'cod',
    ])->assertCreated();

    expect(Cache::get('catalog.public.version'))->toBe(4);

    return collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $sql) => str_contains($sql, 'product_variants') || str_contains($sql, 'products'))
        ->count();
}
