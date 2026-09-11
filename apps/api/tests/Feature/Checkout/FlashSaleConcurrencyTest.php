<?php

use App\Models\Cart;
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
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

it('allows only one checkout to consume the last flash sale unit', function () {
    if (env('DB_CONNECTION') !== 'pgsql') {
        $this->markTestSkipped('Requires PostgreSQL via phpunit.pgsql.xml');
    }

    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.host' => getenv('DB_HOST') ?: 'postgres',
        'database.connections.pgsql.port' => getenv('DB_PORT') ?: '5432',
        'database.connections.pgsql.database' => getenv('DB_DATABASE') ?: 'watch_app_test',
        'database.connections.pgsql.username' => getenv('DB_USERNAME') ?: 'watch',
        'database.connections.pgsql.password' => getenv('DB_PASSWORD') ?: 'watch_secret',
    ]);
    DB::purge('pgsql');
    DB::setDefaultConnection('pgsql');
    DB::table('stock_reservations')->delete();
    DB::table('order_status_histories')->delete();
    DB::table('payment_transactions')->delete();
    DB::table('coupon_redemptions')->delete();
    DB::table('order_items')->delete();
    DB::table('orders')->delete();
    DB::table('cart_items')->delete();
    DB::table('carts')->delete();
    DB::table('flash_sale_items')->delete();
    DB::table('flash_sales')->delete();
    DB::table('stock_items')->delete();
    DB::table('warehouses')->delete();
    DB::table('shipping_rates')->delete();
    DB::table('shipping_methods')->delete();
    DB::table('geo_wards')->delete();
    DB::table('geo_districts')->delete();
    DB::table('geo_provinces')->delete();
    DB::table('product_variants')->delete();
    DB::table('products')->delete();
    $province = GeoProvince::query()->create(['code' => 'FSCON-P', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'FSCON-D', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'FSCON-W', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'flash-concurrent', 'name' => 'Concurrent', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    PaymentMethod::query()->updateOrCreate(['code' => 'cod'], ['name' => 'COD', 'is_active' => true]);
    $warehouse = Warehouse::query()->create(['code' => 'FSCON', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => 100000]);
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 5, 'qty_reserved' => 0]);
    $sale = FlashSale::factory()->create([
        'status' => FlashSale::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);
    $flashItem = FlashSaleItem::factory()->create([
        'flash_sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 70000,
        'qty_cap' => 1,
        'qty_sold' => 0,
    ]);
    $carts = collect([1, 2])->map(function () use ($variant) {
        $cart = Cart::factory()->create(['status' => 'active']);
        $cart->items()->create(['product_variant_id' => $variant->id, 'qty' => 1, 'unit_price' => 70000]);

        return $cart;
    });
    $payload = base64_encode(json_encode(['shipping_address' => ['recipient_name' => 'A', 'phone' => '0', 'province_code' => 'FSCON-P', 'district_code' => 'FSCON-D', 'ward_code' => 'FSCON-W', 'address_line' => 'Road'], 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id, 'payment_method_code' => 'cod']));
    $barrier = sys_get_temp_dir().'/flash-checkout-'.uniqid();
    mkdir($barrier);
    $workerEnv = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_HOST' => 'postgres', 'DB_PORT' => '5432', 'DB_DATABASE' => 'watch_app_test', 'DB_USERNAME' => 'watch', 'DB_PASSWORD' => 'watch_secret'];
    $processes = $carts->values()->map(fn ($cart, $i) => new Process(['php', 'artisan', 'commerce:checkout-worker', (string) $cart->id, $payload, $barrier, (string) $i], base_path(), $workerEnv, timeout: 30));
    $processes->each->start();
    $deadline = microtime(true) + 10;
    while ((! file_exists($barrier.'/ready-0') || ! file_exists($barrier.'/ready-1')) && microtime(true) < $deadline) {
        usleep(10000);
    }
    file_put_contents($barrier.'/release', 'go');
    $processes->each->wait();
    $results = $processes->map(fn ($process) => json_decode(trim($process->getOutput()), true));
    if ($results->contains(null)) {
        throw new RuntimeException($processes->map(fn ($process, $i) => "worker {$i}\nstdout: {$process->getOutput()}\nstderr: {$process->getErrorOutput()}")->implode("\n"));
    }
    expect($results->where('ok', true))->toHaveCount(1)
        ->and($results->pluck('code')->filter()->all())->toContain('FLASH_SALE_QTY_EXCEEDED');
    expect((int) $flashItem->refresh()->qty_sold)->toBe(1);
    expect((int) StockItem::query()->where('product_variant_id', $variant->id)->value('qty_reserved'))->toBe(1);
});
