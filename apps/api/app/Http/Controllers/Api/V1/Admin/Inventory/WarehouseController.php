<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\WarehouseResource;
use App\Models\Warehouse;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paginator = Warehouse::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            WarehouseResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }
}
