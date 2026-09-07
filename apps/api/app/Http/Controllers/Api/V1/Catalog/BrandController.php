<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\PublicBrandResource;
use App\Models\Brand;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = Brand::query()
            ->where('status', Brand::STATUS_ACTIVE)
            ->latest('id')
            ->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            PublicBrandResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }
}
