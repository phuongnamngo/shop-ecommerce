<?php

namespace App\Http\Controllers\Api\V1\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\WarehouseResource;
use App\Models\Warehouse;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class WarehouseController extends Controller
{
    public function index(): JsonResponse
    {
        return ApiResponse::success(WarehouseResource::collection(Warehouse::query()->where('status', 'active')->orderBy('id')->get())->resolve());
    }
}
