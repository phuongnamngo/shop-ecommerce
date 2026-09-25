<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Catalog\PublicBrandResource;
use App\Models\Brand;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use App\Support\CatalogPublicCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function __construct(private readonly CatalogPublicCache $catalogCache) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = CatalogPaginator::perPage($request);
        $page = max(1, $request->integer('page', 1));
        $payload = $this->catalogCache->remember(
            "brands.{$page}.{$perPage}",
            function () use ($perPage): array {
                $paginator = Brand::query()
                    ->where('status', Brand::STATUS_ACTIVE)
                    ->latest('id')
                    ->paginate($perPage);

                return [
                    'data' => PublicBrandResource::collection($paginator->getCollection())->resolve(),
                    'meta' => CatalogPaginator::meta($paginator),
                ];
            },
        );

        return ApiResponse::success($payload['data'], $payload['meta']);
    }
}
