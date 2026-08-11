<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\ProductResource;
use App\Models\Product;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $paginator = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->with('defaultVariant')
            ->latest('id')
            ->paginate(20);

        $items = ProductResource::collection($paginator->getCollection())->resolve();

        return ApiResponse::success($items, [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ]);
    }
}
