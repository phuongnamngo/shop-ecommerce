<?php

namespace App\Services\Dashboard;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockItem;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class DashboardMetricsService
{
    public const PAID_PLUS = ['paid', 'fulfilling', 'shipped', 'completed'];

    /**
     * @return array{
     *     today: array{order_count: int, revenue: string},
     *     month: array{order_count: int, revenue: string},
     *     currency: string,
     *     as_of: string,
     *     revenue_series: list<array{date: string, order_count: int, revenue: string}>,
     *     top_skus: list<array{sku: string, name: string, qty: int, revenue: string}>,
     *     low_stock: list<array{sku: string, name: string, warehouse_code: string, warehouse_name: string, available_qty: int}>,
     *     low_stock_count: int
     * }
     */
    public function snapshot(): array
    {
        $tz = (string) config('app.timezone');
        $now = now($tz);
        $seriesFrom = $now->copy()->subDays(29)->startOfDay();
        $seriesTo = $now->copy()->endOfDay();

        return [
            'today' => $this->periodMetrics($now->copy()->startOfDay(), $now->copy()->endOfDay()),
            'month' => $this->periodMetrics($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            'currency' => 'VND',
            'as_of' => now('UTC')->toISOString(),
            'revenue_series' => $this->revenueSeries($tz, $now, $seriesFrom, $seriesTo),
            'top_skus' => $this->topSkus($seriesFrom, $seriesTo),
            'low_stock' => $this->lowStockPreview(),
            'low_stock_count' => $this->lowStockQuery()->count(),
        ];
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

    /**
     * @return list<array{date: string, order_count: int, revenue: string}>
     */
    private function revenueSeries(string $tz, CarbonInterface $now, CarbonInterface $from, CarbonInterface $to): array
    {
        /** @var array<string, array{date: string, order_count: int, revenue: float}> $buckets */
        $buckets = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $buckets[$date] = ['date' => $date, 'order_count' => 0, 'revenue' => 0.0];
        }

        $orders = Order::query()
            ->whereIn('status', self::PAID_PLUS)
            ->where('currency', 'VND')
            ->whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'grand_total']);

        foreach ($orders as $order) {
            $date = $order->created_at->timezone($tz)->toDateString();
            if (! isset($buckets[$date])) {
                continue;
            }
            $buckets[$date]['order_count']++;
            $buckets[$date]['revenue'] += (float) $order->grand_total;
        }

        return array_values(array_map(static fn (array $row): array => [
            'date' => $row['date'],
            'order_count' => $row['order_count'],
            'revenue' => number_format($row['revenue'], 2, '.', ''),
        ], $buckets));
    }

    /**
     * @return list<array{sku: string, name: string, qty: int, revenue: string}>
     */
    private function topSkus(CarbonInterface $from, CarbonInterface $to): array
    {
        return OrderItem::query()
            ->selectRaw('sku, MAX(name) as name, SUM(qty) as qty, COALESCE(SUM(line_total), 0) as revenue')
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->whereIn('status', DashboardMetricsService::PAID_PLUS)
                    ->where('currency', 'VND')
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->groupBy('sku')
            ->orderByDesc('qty')
            ->orderBy('sku')
            ->limit(10)
            ->get()
            ->map(static fn (OrderItem $row): array => [
                'sku' => $row->sku,
                'name' => (string) $row->name,
                'qty' => (int) $row->qty,
                'revenue' => number_format((float) $row->revenue, 2, '.', ''),
            ])
            ->all();
    }

    /**
     * @return list<array{sku: string, name: string, warehouse_code: string, warehouse_name: string, available_qty: int}>
     */
    private function lowStockPreview(): array
    {
        return $this->lowStockQuery()
            ->with(['warehouse', 'variant.product'])
            ->orderByRaw('(qty_on_hand - qty_reserved) ASC')
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->map(static function (StockItem $item): array {
                $sku = $item->variant->sku;
                $name = $item->variant->product?->name ?: $sku;

                return [
                    'sku' => $sku,
                    'name' => $name,
                    'warehouse_code' => $item->warehouse->code,
                    'warehouse_name' => $item->warehouse->name,
                    'available_qty' => $item->qty_on_hand - $item->qty_reserved,
                ];
            })
            ->all();
    }

    private function lowStockQuery(): Builder
    {
        return StockItem::query()
            ->whereHas('variant')
            ->whereHas('warehouse')
            ->whereRaw('(qty_on_hand - qty_reserved) > 0')
            ->whereRaw('(qty_on_hand - qty_reserved) <= 5');
    }
}
