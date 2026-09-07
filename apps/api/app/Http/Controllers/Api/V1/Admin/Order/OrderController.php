<?php

namespace App\Http\Controllers\Api\V1\Admin\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Order\AdminOrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))->latest();
        $orders = $query->paginate(15);
        return ApiResponse::success(AdminOrderResource::collection($orders->items())->resolve(), ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total()]);
    }
    public function show(int $id): JsonResponse { return ApiResponse::success((new AdminOrderResource(Order::query()->with('items', 'statusHistories')->findOrFail($id)))->resolve()); }
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orders->transition(Order::query()->findOrFail($id), $request->string('status')->toString(), $request->user('admin'), $request->input('note'));
        return ApiResponse::success((new AdminOrderResource($order))->resolve());
    }
}
