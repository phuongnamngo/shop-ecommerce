"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import {
  Banknote,
  Download,
  Package,
  PlusCircle,
  ShoppingBag,
  TrendingUp,
  Users,
  Warehouse,
} from "lucide-react";

import { ChartCard } from "@/components/admin/layout/chart-card";
import {
  DataTableShell,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/admin/layout/data-table";
import { EmptyState, LoadingState } from "@/components/admin/layout/empty-state";
import { StatCard } from "@/components/admin/layout/stat-card";
import { StatusBadge } from "@/components/admin/layout/status-badge";
import { useAdminMe } from "@/hooks/use-admin-me";
import { listCustomers } from "@/lib/api/customers/client";
import { getDashboardMetrics } from "@/lib/api/dashboard/client";
import { dashboardErrorMessage } from "@/lib/api/dashboard/errors";
import { listStockItems } from "@/lib/api/inventory/client";
import { listOrders } from "@/lib/api/orders/client";
import { listProducts } from "@/lib/api/catalog/products";

function formatRevenue(revenue: string, currency: string): string {
  const n = Number.parseFloat(revenue);
  if (!Number.isFinite(n)) {
    return `${revenue} ${currency}`;
  }
  return `${new Intl.NumberFormat("vi-VN").format(n)} ${currency}`;
}

function firstName(name: string): string {
  return name.trim().split(/\s+/)[0] || name;
}

function Sparkline({ color }: { color: string }) {
  const heights = [12, 16, 14, 24, 20, 28, 32];
  return (
    <div className="mt-4 flex h-8 items-end gap-1 border-t border-[#e2e8f0]/80 pt-3">
      {heights.map((h, i) => (
        <div
          key={i}
          className="flex-1 rounded-t"
          style={{
            height: h,
            backgroundColor: color,
            opacity: 0.25 + i * 0.12,
          }}
        />
      ))}
    </div>
  );
}

export function DashboardPage() {
  const me = useAdminMe();
  const metrics = useQuery({
    queryKey: ["admin", "dashboard", "metrics"],
    queryFn: () => getDashboardMetrics(),
  });
  const orders = useQuery({
    queryKey: ["admin", "dashboard", "recent-orders"],
    queryFn: () => listOrders({ page: 1, per_page: 6 }),
  });
  const stock = useQuery({
    queryKey: ["admin", "dashboard", "stock"],
    queryFn: () => listStockItems({ page: 1, per_page: 50 }),
  });
  const customers = useQuery({
    queryKey: ["admin", "dashboard", "customers-count"],
    queryFn: () => listCustomers({ page: 1, per_page: 1 }),
  });
  const products = useQuery({
    queryKey: ["admin", "dashboard", "products"],
    queryFn: () => listProducts({ page: 1, per_page: 5 }),
  });

  const data = metrics.data?.data;
  const today = data ? Number.parseFloat(data.today.revenue) : 0;
  const month = data ? Number.parseFloat(data.month.revenue) : 0;
  const max = Math.max(today, month, 1);
  const aov =
    data && data.month.order_count > 0
      ? month / data.month.order_count
      : 0;
  const stockRows = stock.data?.data ?? [];
  const lowStock = stockRows.filter((item) => item.available_qty > 0 && item.available_qty <= 5);
  const outStock = stockRows.filter((item) => item.available_qty <= 0);
  const inStock = stockRows.filter((item) => item.available_qty > 5);
  const stockTotal = stock.data?.meta.total ?? stockRows.length;
  const inPct = stockRows.length
    ? Math.round((inStock.length / stockRows.length) * 100)
    : 0;
  const greetingName = me.data ? firstName(me.data.name) : "there";
  const pendingHint = orders.data?.meta.total
    ? `${orders.data.meta.total} orders in the queue.`
    : "Here's what's happening with your store today.";

  return (
    <div className="flex flex-col gap-6">
      <section className="relative flex flex-col justify-between gap-6 overflow-hidden rounded-xl border border-[#e2e8f0] bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,0.04)] md:flex-row md:items-center">
        <div className="pointer-events-none absolute -top-12 -right-12 h-64 w-64 rounded-full bg-[#ecf2ff]/80 blur-3xl" />
        <div className="relative z-10 flex items-center gap-4">
          <div className="flex size-14 shrink-0 items-center justify-center rounded-2xl border border-[#1f53c9]/20 bg-gradient-to-br from-[#ecf2ff] to-[#e8f7ff] text-[28px] shadow-sm">
            👋
          </div>
          <div>
            <div className="flex flex-wrap items-center gap-2">
              <h2 className="text-2xl font-bold tracking-tight text-[#0f172a]">
                Welcome back, {greetingName}!
              </h2>
              <span className="inline-flex items-center gap-1 rounded-full bg-[#e6fffa] px-2.5 py-0.5 text-[11px] font-bold text-[#0f766e]">
                <span className="size-1.5 animate-pulse rounded-full bg-[#13deb9]" />
                Live Store
              </span>
            </div>
            <p className="mt-1 text-sm text-[#64748b]">{pendingHint}</p>
          </div>
        </div>
        <div className="relative z-10 flex flex-wrap items-center gap-3">
          <Link
            href="/admin/orders"
            className="inline-flex items-center gap-2 rounded-lg border border-[#e2e8f0] bg-white px-4 py-2 text-sm font-semibold text-[#334155] shadow-xs transition-colors duration-150 hover:bg-[#f8fafc]"
          >
            <Download className="size-[18px]" />
            Orders
          </Link>
          <Link
            href="/admin/catalog/products"
            className="inline-flex items-center gap-2 rounded-lg bg-[#1f53c9] px-4 py-2 text-sm font-semibold text-white shadow-sm shadow-[#1f53c9]/25 transition-all duration-150 hover:bg-[#406de4]"
          >
            <PlusCircle className="size-[18px]" />
            Add Product
          </Link>
        </div>
      </section>

      {metrics.isPending ? (
        <LoadingState label="Loading metrics…" />
      ) : metrics.isError ? (
        <p className="text-sm text-[#ba1a1a]">
          {dashboardErrorMessage(metrics.error)}
        </p>
      ) : data ? (
        <>
          <section className="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
              label="Total Revenue"
              value={formatRevenue(data.month.revenue, data.currency)}
              hint="Paid+ this month"
              icon={<Banknote className="size-5" />}
              tone="primary"
              footer={<Sparkline color="#1f53c9" />}
            />
            <StatCard
              label="Total Orders"
              value={data.month.order_count}
              hint={`${data.today.order_count} today`}
              icon={<ShoppingBag className="size-5" />}
              tone="secondary"
              footer={<Sparkline color="#48bdfe" />}
            />
            <StatCard
              label="Total Customers"
              value={customers.data?.meta.total ?? "—"}
              hint="Directory total"
              icon={<Users className="size-5" />}
              tone="success"
              footer={<Sparkline color="#13deb9" />}
            />
            <StatCard
              label="Inventory Health"
              value={stockTotal}
              hint={
                <span className="flex flex-wrap items-center gap-2">
                  <span className="rounded-full bg-[#fef5e5] px-2.5 py-0.5 text-[11px] font-semibold text-[#b45309]">
                    {lowStock.length} Low Stock
                  </span>
                  <span className="text-[11px] font-medium text-[#fa896b]">
                    {outStock.length} Out
                  </span>
                </span>
              }
              icon={<Warehouse className="size-5" />}
              tone="warning"
              footer={
                <div className="mt-4 flex h-2.5 overflow-hidden rounded-full bg-[#e2e8f0]">
                  <div className="bg-[#13deb9]" style={{ width: `${inPct}%` }} />
                  <div
                    className="bg-[#f59e0b]"
                    style={{
                      width: `${stockRows.length ? (lowStock.length / stockRows.length) * 100 : 0}%`,
                    }}
                  />
                  <div
                    className="bg-[#fa896b]"
                    style={{
                      width: `${stockRows.length ? (outStock.length / stockRows.length) * 100 : 0}%`,
                    }}
                  />
                </div>
              }
            />
          </section>

          <section className="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <ChartCard
              className="lg:col-span-8"
              title="Revenue Updates"
              badge={
                <span className="rounded border border-[#e2e8f0] bg-[#f8fafc] px-2 py-0.5 text-[11px] text-[#64748b]">
                  Live totals
                </span>
              }
              subtitle="Time-series is not provided by the metrics API. Bars compare today vs this month."
            >
              <div className="mb-4 grid grid-cols-3 gap-4 border-b border-[#e2e8f0]/60 pb-4">
                <div>
                  <p className="text-[11px] text-[#64748b]">Current Month Revenue</p>
                  <p className="mt-0.5 text-lg font-bold text-[#0f172a]">
                    {formatRevenue(data.month.revenue, data.currency)}
                  </p>
                </div>
                <div>
                  <p className="text-[11px] text-[#64748b]">Today Revenue</p>
                  <p className="mt-0.5 text-lg font-bold text-[#0f172a]">
                    {formatRevenue(data.today.revenue, data.currency)}
                  </p>
                </div>
                <div>
                  <p className="text-[11px] text-[#64748b]">Average Order Value</p>
                  <p className="mt-0.5 text-lg font-bold text-[#0f172a]">
                    {data.month.order_count
                      ? `${new Intl.NumberFormat("vi-VN").format(Math.round(aov))} ${data.currency}`
                      : "—"}
                  </p>
                </div>
              </div>
              <div className="flex h-56 items-end justify-around gap-10 px-4 pb-2">
                <div className="flex flex-col items-center gap-2">
                  <div
                    className="w-10 rounded-t-sm bg-[#1f53c9] transition-all duration-200 group-hover:brightness-110"
                    style={{ height: `${Math.max(12, (today / max) * 180)}px` }}
                  />
                  <span className="text-[11px] text-[#64748b]">Today</span>
                </div>
                <div className="flex flex-col items-center gap-2">
                  <div
                    className="w-10 rounded-t-sm bg-[#48bdfe]"
                    style={{ height: `${Math.max(12, (month / max) * 180)}px` }}
                  />
                  <span className="text-[11px] text-[#64748b]">This month</span>
                </div>
              </div>
            </ChartCard>

            <ChartCard
              className="lg:col-span-4"
              title="Inventory Alerts"
              subtitle="Available quantity ≤ 5 on the current stock page"
            >
              {stock.isPending ? (
                <LoadingState />
              ) : lowStock.length === 0 && outStock.length === 0 ? (
                <EmptyState title="No low-stock rows" />
              ) : (
                <ul className="space-y-3">
                  {[...outStock, ...lowStock].slice(0, 6).map((item) => (
                    <li
                      key={item.id}
                      className="flex items-center justify-between text-sm"
                    >
                      <span className="truncate pr-3 text-[#0f172a]">
                        {item.variant.product_name ?? item.variant.sku}
                      </span>
                      <span className="font-semibold text-[#b45309] tabular-nums">
                        {item.available_qty}
                      </span>
                    </li>
                  ))}
                </ul>
              )}
              <Link
                href="/admin/inventory"
                className="mt-4 inline-flex h-9 items-center rounded-lg border border-[#e2e8f0] px-3 text-sm font-semibold text-[#334155] hover:bg-[#f8fafc]"
              >
                Restock
              </Link>
            </ChartCard>
          </section>
        </>
      ) : null}

      <section className="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div className="xl:col-span-8">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-lg font-bold text-[#0f172a]">Recent Orders</h2>
            <Link
              href="/admin/orders"
              className="text-sm font-semibold text-[#1f53c9] hover:underline"
            >
              View all
            </Link>
          </div>
          <DataTableShell>
            {orders.isPending ? (
              <LoadingState />
            ) : (orders.data?.data ?? []).length === 0 ? (
              <EmptyState title="No orders yet" />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Number</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Grand total</TableHead>
                    <TableHead>Created</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {(orders.data?.data ?? []).map((order) => (
                    <TableRow key={order.id}>
                      <TableCell className="font-medium">
                        <Link
                          href={`/admin/orders/${order.id}`}
                          className="text-[#1f53c9] hover:underline"
                        >
                          {order.number}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <StatusBadge status={String(order.status)} />
                      </TableCell>
                      <TableCell className="text-right tabular-nums">
                        {order.grand_total} {order.currency}
                      </TableCell>
                      <TableCell className="text-[#64748b]">
                        {order.created_at
                          ? new Date(order.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </DataTableShell>
        </div>
        <div className="xl:col-span-4">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="text-lg font-bold text-[#0f172a]">Top Products</h2>
            <Link
              href="/admin/catalog/products"
              className="text-sm font-semibold text-[#1f53c9] hover:underline"
            >
              Catalog
            </Link>
          </div>
          <DataTableShell>
            {products.isPending ? (
              <LoadingState />
            ) : (products.data?.data ?? []).length === 0 ? (
              <EmptyState title="No products" />
            ) : (
              <ul className="divide-y divide-[#e2e8f0]">
                {(products.data?.data ?? []).map((product) => {
                  const thumb =
                    product.images.find((img) => img.is_primary)?.thumbnail_url ??
                    product.images[0]?.thumbnail_url ??
                    product.images[0]?.url;
                  return (
                    <li key={product.id} className="flex items-center gap-3 px-4 py-3">
                      <div className="flex size-10 overflow-hidden rounded-lg bg-[#f8fafc]">
                        {thumb ? (
                          // eslint-disable-next-line @next/next/no-img-element
                          <img
                            src={thumb}
                            alt=""
                            className="size-10 object-cover transition-transform duration-200 hover:scale-110"
                          />
                        ) : (
                          <Package className="m-auto size-4 text-[#64748b]" />
                        )}
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold text-[#0f172a]">
                          {product.name}
                        </p>
                        <p className="text-[11px] text-[#64748b]">{product.status}</p>
                      </div>
                      <TrendingUp className="size-4 text-[#13deb9]" />
                    </li>
                  );
                })}
              </ul>
            )}
          </DataTableShell>
        </div>
      </section>
    </div>
  );
}
