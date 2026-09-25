<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

final class CatalogPublicCache
{
    public const TTL_SECONDS = 60;

    private const VERSION_KEY = 'catalog.public.version';

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function remember(string $suffix, Closure $callback): mixed
    {
        return Cache::remember($this->key($suffix), self::TTL_SECONDS, $callback);
    }

    public function bump(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::put(self::VERSION_KEY, 1);
        }

        Cache::increment(self::VERSION_KEY);
    }

    public function key(string $suffix): string
    {
        return 'catalog.public.v'.$this->version().'.'.$suffix;
    }
}
