<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()->where('customer_id', $request->user('customer')->id)->latest()->paginate(15);
        return ApiResponse::success(OrderResource::collection($orders->items())->resolve(), ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total()]);
    }
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::query()->where('customer_id', $request->user('customer')->id)->with('items', 'statusHistories')->findOrFail($id);
        return ApiResponse::success((new OrderResource($order))->resolve());
    }
}
