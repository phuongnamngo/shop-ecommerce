<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CmsSlug
{
    public static function resolve(string $title, ?string $requestedSlug): string
    {
        $slug = is_string($requestedSlug) ? trim($requestedSlug) : '';

        return $slug !== '' ? $slug : Str::slug($title);
    }

    public static function assertUnique(string $slug, ?int $ignoreId = null): void
    {
        $query = DB::table('cms_pages')->where('slug', $slug)->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new CmsException(
                ErrorCode::CMS_SLUG_TAKEN,
                'Slug has already been taken.',
                'slug',
                422,
            );
        }
    }

    public static function forUpdate(string $currentSlug, string $title, ?string $requestedSlug, int $id): string
    {
        if (! is_string($requestedSlug) || trim($requestedSlug) === '') {
            return $currentSlug;
        }

        $slug = trim($requestedSlug);
        self::assertUnique($slug, $id);

        return $slug;
    }
}
