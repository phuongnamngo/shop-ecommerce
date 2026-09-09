<?php

namespace App\Http\Controllers\Api\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ApiResponse;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    private const PAID_PLUS = ['paid', 'fulfilling', 'shipped', 'completed'];

    public function __invoke(): JsonResponse
    {
        $tz = config('app.timezone');
        $now = now($tz);

        return ApiResponse::success([
            'today' => $this->periodMetrics($now->copy()->startOfDay(), $now->copy()->endOfDay()),
            'month' => $this->periodMetrics($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            'currency' => 'VND',
            'as_of' => now('UTC')->toISOString(),
        ]);
    }

    /**
     * @return array{order_count: int, revenue: string}
     */
    private function periodMetrics(CarbonInterface $from, CarbonInterface $to): array
    {
        $row = Order::query()
            ->whereIn('status', self::PAID_PLUS)
            ->where('currency', 'VND')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as order_count, COALESCE(SUM(grand_total), 0) as revenue')
            ->first();

        return [
            'order_count' => (int) $row->order_count,
            'revenue' => number_format((float) $row->revenue, 2, '.', ''),
        ];
    }
}
