<?php

use App\Models\Cart;
use App\Models\GeoDistrict;
use App\Models\GeoProvince;
use App\Models\GeoWard;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\StockItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

it('allows only one checkout to reserve the last unit', function () {
    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql.host' => 'postgres',
        'database.connections.pgsql.port' => '5432',
        'database.connections.pgsql.database' => 'watch_app_test',
        'database.connections.pgsql.username' => 'watch',
        'database.connections.pgsql.password' => 'watch_secret',
    ]);
    DB::purge('pgsql');
    DB::setDefaultConnection('pgsql');
    DB::table('stock_reservations')->delete();
    DB::table('order_status_histories')->delete();
    DB::table('order_items')->delete();
    DB::table('orders')->delete();
    DB::table('cart_items')->delete();
    DB::table('carts')->delete();
    DB::table('stock_items')->delete();
    DB::table('warehouses')->delete();
    DB::table('shipping_rates')->delete();
    DB::table('shipping_methods')->delete();
    DB::table('geo_wards')->delete();
    DB::table('geo_districts')->delete();
    DB::table('geo_provinces')->delete();
    DB::table('product_variants')->delete();
    DB::table('products')->delete();
    $province = GeoProvince::query()->create(['code' => 'CON-P', 'name' => 'Province']);
    $district = GeoDistrict::query()->create(['geo_province_id' => $province->id, 'code' => 'CON-D', 'name' => 'District']);
    GeoWard::query()->create(['geo_district_id' => $district->id, 'code' => 'CON-W', 'name' => 'Ward']);
    $method = ShippingMethod::query()->create(['code' => 'concurrent', 'name' => 'Concurrent', 'status' => 'active']);
    $rate = ShippingRate::query()->create(['shipping_method_id' => $method->id, 'price' => 0]);
    $warehouse = Warehouse::query()->create(['code' => 'CON', 'name' => 'Default', 'is_default' => true, 'status' => 'active']);
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    StockItem::query()->create(['warehouse_id' => $warehouse->id, 'product_variant_id' => $variant->id, 'qty_on_hand' => 1, 'qty_reserved' => 0]);
    $carts = collect([1, 2])->map(function () use ($variant) {
        $cart = Cart::factory()->create(['status' => 'active']);
        $cart->items()->create(['product_variant_id' => $variant->id, 'qty' => 1, 'unit_price' => $variant->price]);

        return $cart;
    });
    $payload = base64_encode(json_encode(['shipping_address' => ['recipient_name' => 'A', 'phone' => '0', 'province_code' => 'CON-P', 'district_code' => 'CON-D', 'ward_code' => 'CON-W', 'address_line' => 'Road'], 'shipping_method_id' => $method->id, 'shipping_rate_id' => $rate->id]));
    $barrier = sys_get_temp_dir().'/checkout-'.uniqid();
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
    expect($results->where('ok', true))->toHaveCount(1)->and($results->pluck('code')->filter()->all())->toContain('INVENTORY_INSUFFICIENT_STOCK');
    expect(StockItem::query()->where('product_variant_id', $variant->id)->value('qty_reserved'))->toBe(1);
});
