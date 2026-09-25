<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Services\Catalog\CatalogCategoryService;
use App\Support\ApiResponse;
use App\Support\CatalogPublicCache;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CatalogCategoryService $categories,
        private readonly CatalogPublicCache $catalogCache,
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success($this->catalogCache->remember(
            'categories',
            fn (): array => $this->categories->publicTree(),
        ));
    }
}
