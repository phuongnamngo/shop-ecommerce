<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CatalogSlug
{
    /**
     * @param  'brands'|'categories'|'products'|'attributes'  $table
     */
    public static function resolve(string $name, ?string $requestedSlug): string
    {
        $slug = is_string($requestedSlug) ? trim($requestedSlug) : '';

        return $slug !== '' ? $slug : Str::slug($name);
    }

    /**
     * @param  'brands'|'categories'|'products'|'attributes'  $table
     */
    public static function assertUnique(string $table, string $slug, ?int $ignoreId = null): void
    {
        $query = DB::table($table)->where('slug', $slug)->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new CatalogException(
                ErrorCode::CATALOG_SLUG_TAKEN,
                'Slug has already been taken.',
                'slug',
                422,
            );
        }
    }

    /**
     * Keep existing slug unless the client sent a new one.
     *
     * @param  'brands'|'categories'|'products'|'attributes'  $table
     */
    public static function forUpdate(string $table, string $currentSlug, string $name, ?string $requestedSlug, int $id): string
    {
        if (! is_string($requestedSlug) || trim($requestedSlug) === '') {
            return $currentSlug;
        }

        $slug = trim($requestedSlug);
        self::assertUnique($table, $slug, $id);

        return $slug;
    }
}
