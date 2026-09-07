<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

final class CatalogPaginator
{
    public static function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        $value = $request->integer('per_page', $default);

        if ($value < 1) {
            return $default;
        }

        return min($value, $max);
    }

    /**
     * @return array{current_page: int, per_page: int, total: int, last_page: int}
     */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
