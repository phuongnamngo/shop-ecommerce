<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\StockItemResource;
use App\Models\StockItem;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StockItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StockItem::query()->orderBy('id');
        if ($request->filled('warehouse_id')) $query->where('warehouse_id', $request->integer('warehouse_id'));
        if ($request->filled('product_variant_id')) $query->where('product_variant_id', $request->integer('product_variant_id'));
        return ApiResponse::success(StockItemResource::collection($query->get())->resolve());
    }
}
