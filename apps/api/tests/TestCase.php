<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Meilisearch\Client;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        putenv('SCOUT_PREFIX=testing_');
        putenv('SCOUT_QUEUE=false');
        putenv('QUEUE_CONNECTION=sync');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        putenv('DB_URL=');
        $_ENV['SCOUT_PREFIX'] = $_SERVER['SCOUT_PREFIX'] = 'testing_';
        $_ENV['SCOUT_QUEUE'] = $_SERVER['SCOUT_QUEUE'] = 'false';
        $_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
        $_ENV['DB_URL'] = $_SERVER['DB_URL'] = '';

        parent::setUp();

        config([
            'scout.prefix' => 'testing_',
            'scout.queue' => false,
            'scout.driver' => 'meilisearch',
            'queue.default' => 'sync',
            'database.default' => 'sqlite',
        ]);
        $this->app->forgetInstance(Client::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000',
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
