"use client";

import { useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { Eye } from "lucide-react";

import {
  AdminPagination,
  DataTableShell,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/admin/layout/data-table";
import { EmptyState, LoadingState } from "@/components/admin/layout/empty-state";
import { PageHeader } from "@/components/admin/layout/page-header";
import { StatusBadge } from "@/components/admin/layout/status-badge";
import { OrderInspectDialog } from "@/components/admin/orders/order-inspect-dialog";
import { Button } from "@/components/ui/button";
import { listOrders } from "@/lib/api/orders/client";
import { orderErrorMessage } from "@/lib/api/orders/errors";
import { cn } from "@/lib/utils";

const STATUS_TABS = [
  "all",
  "pending",
  "paid",
  "fulfilling",
  "shipped",
  "completed",
  "cancelled",
] as const;

function parsePositiveInt(raw: string | null): number | undefined {
  if (raw === null || raw === "") return undefined;
  const n = Number.parseInt(raw, 10);
  if (!Number.isFinite(n) || n <= 0) return undefined;
  return n;
}

export function OrdersListPage() {
  const searchParams = useSearchParams();
  const customerId = parsePositiveInt(searchParams.get("customer_id"));

  const [page, setPage] = useState(1);
  const [status, setStatus] = useState("all");
  const [inspectId, setInspectId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ["admin", "orders", page, status, customerId ?? null],
    queryFn: () =>
      listOrders({
        page,
        per_page: 15,
        status: status === "all" ? undefined : status,
        customer_id: customerId,
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Order Management"
        description="Fulfillment queue — inspect an order in the overlay or open the dedicated page."
      />

      {customerId !== undefined ? (
        <div className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-[#e2e8f0] bg-white px-4 py-2 text-sm">
          <span>Filtered by customer #{customerId}</span>
          <Button asChild variant="outline" size="sm">
            <Link href="/admin/orders">Clear filter</Link>
          </Button>
        </div>
      ) : null}

      <div className="flex flex-wrap gap-1 rounded-xl border border-[#e2e8f0] bg-white p-1">
        {STATUS_TABS.map((tab) => (
          <button
            key={tab}
            type="button"
            onClick={() => {
              setStatus(tab);
              setPage(1);
            }}
            className={cn(
              "rounded-lg px-3 py-1.5 text-[13px] font-semibold capitalize transition-colors duration-150",
              status === tab
                ? "bg-[#ecf2ff] text-[#1f53c9]"
                : "text-[#64748b] hover:text-[#0f172a]",
            )}
          >
            {tab}
          </button>
        ))}
      </div>

      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {orderErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No orders in this filter" />
        ) : (
          <>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Number</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Grand total</TableHead>
                    <TableHead>Customer ID</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((order) => (
                    <TableRow key={order.id}>
                      <TableCell className="font-medium">{order.number}</TableCell>
                      <TableCell>
                        <StatusBadge status={String(order.status)} />
                      </TableCell>
                      <TableCell className="text-right tabular-nums">
                        {order.grand_total} {order.currency}
                      </TableCell>
                      <TableCell>{order.customer_id ?? "—"}</TableCell>
                      <TableCell className="text-[13px] text-[#64748b]">
                        {order.created_at
                          ? new Date(order.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            onClick={() => setInspectId(order.id)}
                            aria-label="Inspect order"
                          >
                            <Eye className="size-4" />
                          </Button>
                          <Button asChild variant="outline" size="sm">
                            <Link href={`/admin/orders/${order.id}`}>Open</Link>
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {meta ? (
              <AdminPagination
                page={page}
                lastPage={meta.last_page}
                total={meta.total}
                onPrev={() => setPage((p) => p - 1)}
                onNext={() => setPage((p) => p + 1)}
              />
            ) : null}
          </>
        )}
      </DataTableShell>

      <OrderInspectDialog
        orderId={inspectId}
        onOpenChange={(open) => {
          if (!open) setInspectId(null);
        }}
      />
    </div>
  );
}
