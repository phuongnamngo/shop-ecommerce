"use client";

import { useQuery } from "@tanstack/react-query";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { getDashboardMetrics } from "@/lib/api/dashboard/client";
import { dashboardErrorMessage } from "@/lib/api/dashboard/errors";

function formatRevenue(revenue: string, currency: string): string {
  const n = Number.parseFloat(revenue);
  if (!Number.isFinite(n)) {
    return `${revenue} ${currency}`;
  }
  return `${new Intl.NumberFormat("vi-VN").format(n)} ${currency}`;
}

export function DashboardPage() {
  const query = useQuery({
    queryKey: ["admin", "dashboard", "metrics"],
    queryFn: () => getDashboardMetrics(),
  });

  const data = query.data?.data;

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">Dashboard</h1>
        <p className="text-sm text-muted-foreground">
          Đơn Paid+ và doanh thu theo ngày / tháng
        </p>
      </div>

      {query.isPending ? (
        <p className="text-sm text-muted-foreground">Đang tải…</p>
      ) : query.isError ? (
        <p className="text-sm text-destructive">
          {dashboardErrorMessage(query.error)}
        </p>
      ) : data ? (
        <>
          <div className="grid gap-4 sm:grid-cols-2">
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>Hôm nay · đơn</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                  {data.today.order_count}
                </CardTitle>
              </CardHeader>
              <CardContent />
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>Hôm nay · doanh thu</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                  {formatRevenue(data.today.revenue, data.currency)}
                </CardTitle>
              </CardHeader>
              <CardContent />
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>Tháng này · đơn</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                  {data.month.order_count}
                </CardTitle>
              </CardHeader>
              <CardContent />
            </Card>
            <Card>
              <CardHeader className="pb-2">
                <CardDescription>Tháng này · doanh thu</CardDescription>
                <CardTitle className="text-3xl tabular-nums">
                  {formatRevenue(data.month.revenue, data.currency)}
                </CardTitle>
              </CardHeader>
              <CardContent />
            </Card>
          </div>
          <p className="text-xs text-muted-foreground">
            Paid+ · created_at · UTC
          </p>
        </>
      ) : null}
    </div>
  );
}
