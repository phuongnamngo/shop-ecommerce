<?php

namespace App\Http\Controllers\Api\V1\Admin\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Order\AdminOrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 15, example: 15)]
    #[QueryParameter('status', description: 'Filter by order status.', type: 'string', example: 'pending')]
    #[Response(200, 'Paginated orders.', type: 'array{data: list<AdminOrderResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))->latest();
        $orders = $query->paginate(CatalogPaginator::perPage($request, 15));

        return ApiResponse::success(AdminOrderResource::collection($orders->items())->resolve(), ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total()]);
    }

    #[Response(200, 'Order detail.', type: 'array{data: AdminOrderResource, meta: object}')]
    public function show(int $id): JsonResponse
    {
        return ApiResponse::success((new AdminOrderResource(
            Order::query()->with(['items.variant.product', 'statusHistories', 'shipments'])->findOrFail($id),
        ))->resolve());
    }

    #[Response(200, 'Updated order.', type: 'array{data: AdminOrderResource, meta: object}')]
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orders->transition(Order::query()->findOrFail($id), $request->string('status')->toString(), $request->user('admin'), $request->input('note'));

        return ApiResponse::success((new AdminOrderResource($order))->resolve());
    }
}
