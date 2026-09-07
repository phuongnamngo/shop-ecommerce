<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Services\Checkout\CheckoutService;
use App\Support\CommerceException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class CheckoutWorkerCommand extends Command
{
    protected $signature = 'commerce:checkout-worker {cart} {payload} {barrier} {worker}';
    protected $description = 'Internal PostgreSQL checkout concurrency test worker';

    public function handle(CheckoutService $checkout): int
    {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.host' => getenv('DB_HOST') ?: 'postgres',
            'database.connections.pgsql.port' => getenv('DB_PORT') ?: '5432',
            'database.connections.pgsql.database' => getenv('DB_DATABASE') ?: 'watch_app_test',
            'database.connections.pgsql.username' => getenv('DB_USERNAME') ?: 'watch',
            'database.connections.pgsql.password' => getenv('DB_PASSWORD') ?: '',
        ]);
        DB::purge('pgsql');
        DB::setDefaultConnection('pgsql');

        $barrier = rtrim($this->argument('barrier'), '/');
        file_put_contents($barrier.'/ready-'.$this->argument('worker'), 'ready');
        $deadline = microtime(true) + 15;
        while (! file_exists($barrier.'/release') && microtime(true) < $deadline) usleep(10000);

        try {
            $order = $checkout->checkout(Cart::query()->findOrFail((int) $this->argument('cart')), null, json_decode(base64_decode($this->argument('payload')), true, flags: JSON_THROW_ON_ERROR));
            $this->line(json_encode(['ok' => true, 'order_id' => $order->id]));
        } catch (CommerceException $e) {
            $this->line(json_encode(['ok' => false, 'code' => $e->errorCode]));
        }
        return self::SUCCESS;
    }
}
