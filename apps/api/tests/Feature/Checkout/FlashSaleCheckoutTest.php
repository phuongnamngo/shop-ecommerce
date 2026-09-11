<?php

use App\Models\Coupon;
use App\Models\Discount;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

function flashCheckoutScene(int $listPrice = 100000, int $stock = 5): array
{
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    $suffix = Str::lower(Str::random(4));
    $province = GeoProvince::query()->create(['code' => 'FS-P-'.$suffix, 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'FS-D-'.$suffix, 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'FS-W-'.$suffix, 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'flash-'.$suffix, 'name' => 'Flash', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 10000]);
    $warehouse = Warehouse::query()->create(['code' => 'FS-'.$suffix, 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => $listPrice]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => $stock, 'qty_reserved' => 0]);

    return [
        'product' => $product->refresh(),
        'variant' => $variant->refresh(),
        'method' => $method,
        'rate' => $rate,
        'payload' => [
            'shipping_address' => [
                'recipient_name' => 'A',
                'phone' => '0900000000',
                'province_code' => $province->code,
                'district_code' => $district->code,
                'ward_code' => 'FS-W-'.$suffix,
                'address_line' => 'Road',
            ],
            'shipping_method_id' => $method->id,
            'shipping_rate_id' => $rate->id,
            'payment_method_code' => 'cod',
        ],
    ];
}

function activeFlashItem($variant, string $salePrice, ?int $qtyCap = null, int $qtySold = 0): FlashSaleItem
{
    $sale = FlashSale::factory()->create([
        'status' => FlashSale::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHours(2),
    ]);

    return FlashSaleItem::factory()->create([
        'flash_sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'sale_price' => $salePrice,
        'qty_cap' => $qtyCap,
        'qty_sold' => $qtySold,
    ]);
}

it('snapshots the flash sale unit price on cart add and refresh', function () {
    $scene = flashCheckoutScene(120000);
    $variant = $scene['variant'];
    $token = test()->postJson('/api/v1/cart')->assertCreated()->json('meta.cart_token');

    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])
        ->assertCreated()
        ->assertJsonPath('data.items.0.unit_price', '120000.00');

    $item = activeFlashItem($variant, '79000', 10);

    test()->withHeader('X-Cart-Token', $token)
        ->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.items.0.unit_price', '79000.00')
        ->assertJsonPath('data.subtotal', '79000.00');

    expect($item->refresh()->qty_sold)->toBe(0);
});

it('checks out at the flash sale price and applies coupon on the flash subtotal', function () {
    $scene = flashCheckoutScene(100000);
    $variant = $scene['variant'];
    $item = activeFlashItem($variant, '70000', 10);
    $discount = Discount::query()->create([
        'code' => (string) Str::ulid(),
        'name' => 'Ten percent',
        'type' => 'percentage',
        'value' => 10,
        'status' => 'active',
    ]);
    Coupon::query()->create(['code' => 'FLASH10', 'discount_id' => $discount->id, 'used_count' => 0, 'status' => 'active']);
    $token = test()->postJson('/api/v1/cart')->json('meta.cart_token');
    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 1])
        ->assertCreated();

    $response = test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/checkout', [...$scene['payload'], 'coupon_code' => 'FLASH10'])
        ->assertCreated()
        ->assertJsonPath('data.subtotal', '70000.00')
        ->assertJsonPath('data.discount_total', '7000.00')
        ->assertJsonPath('data.grand_total', '73000.00');

    test()->assertDatabaseHas('order_items', [
        'order_id' => $response->json('data.id'),
        'unit_price' => 70000,
        'flash_sale_item_id' => $item->id,
    ]);
    expect((int) $item->refresh()->qty_sold)->toBe(1);
});

it('rejects checkout when flash qty_cap would be exceeded', function () {
    $scene = flashCheckoutScene(100000, 5);
    $variant = $scene['variant'];
    activeFlashItem($variant, '70000', 1);
    $token = test()->postJson('/api/v1/cart')->json('meta.cart_token');
    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])
        ->assertCreated();

    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/checkout', $scene['payload'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', ErrorCode::FLASH_SALE_QTY_EXCEEDED);
    test()->assertDatabaseCount('orders', 0);
});

it('prefers insufficient stock over flash qty when stock fails first', function () {
    $scene = flashCheckoutScene(100000, 1);
    $variant = $scene['variant'];
    activeFlashItem($variant, '70000', 10);
    $token = test()->postJson('/api/v1/cart')->json('meta.cart_token');
    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2]);

    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/checkout', $scene['payload'])
        ->assertConflict()
        ->assertJsonPath('errors.0.code', ErrorCode::INVENTORY_INSUFFICIENT_STOCK);
});

it('releases flash qty_sold when the pending order is cancelled', function () {
    test()->seed(RolesAndPermissionsSeeder::class);
    $scene = flashCheckoutScene(100000);
    $variant = $scene['variant'];
    $item = activeFlashItem($variant, '70000', 5);
    $token = test()->postJson('/api/v1/cart')->json('meta.cart_token');
    test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/cart/items', ['product_variant_id' => $variant->id, 'qty' => 2])
        ->assertCreated();

    $orderId = test()->withHeader('X-Cart-Token', $token)
        ->postJson('/api/v1/checkout', $scene['payload'])
        ->assertCreated()
        ->json('data.id');

    expect((int) $item->refresh()->qty_sold)->toBe(2);

    test()->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/orders/'.$orderId.'/status', ['status' => 'cancelled'])
        ->assertOk();

    expect((int) $item->refresh()->qty_sold)->toBe(0);
});

it('overlays flash sale price on the public product detail', function () {
    $scene = flashCheckoutScene(150000);
    $variant = $scene['variant'];
    $item = activeFlashItem($variant, '99000', 4, 1);

    test()->getJson('/api/v1/catalog/products/'.$scene['product']->slug)
        ->assertOk()
        ->assertJsonPath('data.default_variant.price', '99000.00')
        ->assertJsonPath('data.default_variant.compare_at_price', '150000.00')
        ->assertJsonPath('data.default_variant.flash_sale.id', $item->flash_sale_id)
        ->assertJsonPath('data.default_variant.flash_sale.qty_remaining', 3)
        ->assertJsonPath('data.variants.0.price', '99000.00')
        ->assertJsonPath('data.variants.0.compare_at_price', '150000.00');
});
