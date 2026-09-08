<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    #[QueryParameter('page', description: 'Page number.', type: 'int', default: 1, example: 1)]
    #[QueryParameter('per_page', description: 'Items per page (maximum 100).', type: 'int', default: 15, example: 15)]
    #[Response(200, 'Paginated customer orders.', type: 'array{data: list<OrderResource>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}')]
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()->where('customer_id', $request->user('customer')->id)->latest()->paginate(CatalogPaginator::perPage($request, 15));

        return ApiResponse::success(OrderResource::collection($orders->items())->resolve(), ['current_page' => $orders->currentPage(), 'last_page' => $orders->lastPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total()]);
    }

    #[Response(200, 'Customer order detail.', type: 'array{data: OrderResource, meta: object}')]
    public function show(Request $request, int $id): JsonResponse
    {
        $order = Order::query()->where('customer_id', $request->user('customer')->id)->with('items', 'statusHistories')->findOrFail($id);

        return ApiResponse::success((new OrderResource($order))->resolve());
    }
}
