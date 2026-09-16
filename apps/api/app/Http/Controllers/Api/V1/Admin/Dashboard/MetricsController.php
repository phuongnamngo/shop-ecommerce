<?php

namespace App\Http\Controllers\Api\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics) {}

    #[Response(200, 'Admin dashboard metrics.', type: 'array{data: array{today: array{order_count: int, revenue: string}, month: array{order_count: int, revenue: string}, currency: string, as_of: string, revenue_series: list<array{date: string, order_count: int, revenue: string}>, top_skus: list<array{sku: string, name: string, qty: int, revenue: string}>, low_stock: list<array{sku: string, name: string, warehouse_code: string, warehouse_name: string, available_qty: int}>, low_stock_count: int}, meta: object}')]
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success($this->metrics->snapshot());
    }
}
