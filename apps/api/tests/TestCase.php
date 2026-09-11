<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Meilisearch\Client;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var list<string|null>
     */
    protected $connectionsToTransact = [null];

    protected function setUp(): void
    {
        if (str_contains(static::class, 'ConcurrencyTest')) {
            $this->connectionsToTransact = [];
        }

        $usePgsql = ($_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION')) === 'pgsql'
            || ($_SERVER['DB_CONNECTION'] ?? null) === 'pgsql';

        putenv('SCOUT_PREFIX=testing_');
        putenv('SCOUT_QUEUE=false');
        putenv('QUEUE_CONNECTION=sync');
        $_ENV['SCOUT_PREFIX'] = $_SERVER['SCOUT_PREFIX'] = 'testing_';
        $_ENV['SCOUT_QUEUE'] = $_SERVER['SCOUT_QUEUE'] = 'false';
        $_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';

        if (! $usePgsql) {
            putenv('DB_CONNECTION=sqlite');
            putenv('DB_DATABASE=:memory:');
            putenv('DB_URL=');
            $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
            $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
            $_ENV['DB_URL'] = $_SERVER['DB_URL'] = '';
        } else {
            $database = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'watch_app_test';
            if ($database === 'watch_app') {
                $database = 'watch_app_test';
            }
            putenv('DB_CONNECTION=pgsql');
            putenv('DB_DATABASE='.$database);
            putenv('DB_HOST='.(($_ENV['DB_HOST'] ?? getenv('DB_HOST')) ?: 'postgres'));
            putenv('DB_PORT='.(($_ENV['DB_PORT'] ?? getenv('DB_PORT')) ?: '5432'));
            putenv('DB_USERNAME='.(($_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME')) ?: 'watch'));
            putenv('DB_PASSWORD='.(($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD')) ?: 'watch_secret'));
            $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'pgsql';
            $_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $database;
        }

        parent::setUp();

        config([
            'scout.prefix' => 'testing_',
            'scout.queue' => false,
            'scout.driver' => 'meilisearch',
            'queue.default' => 'sync',
            'database.default' => $usePgsql ? 'pgsql' : 'sqlite',
        ]);
        $this->app->forgetInstance(Client::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000',
        ]);

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }
}
