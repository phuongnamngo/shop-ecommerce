"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import { StatusBadge } from "@/components/admin/layout/status-badge";
import { LoadingState } from "@/components/admin/layout/empty-state";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { getOrder } from "@/lib/api/orders/client";
import { orderErrorMessage } from "@/lib/api/orders/errors";

export function OrderInspectDialog({
  orderId,
  onOpenChange,
}: {
  orderId: number | null;
  onOpenChange: (open: boolean) => void;
}) {
  const query = useQuery({
    queryKey: ["admin", "orders", orderId],
    queryFn: () => getOrder(orderId!),
    enabled: orderId != null,
  });

  const order = query.data?.data;
  const items = order?.items ?? [];
  const history = order?.status_history ?? [];
  const address = order?.shipping_address;

  return (
    <Dialog open={orderId != null} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl sm:rounded-xl">
        <DialogHeader>
          <DialogTitle>
            {order ? `Order ${order.number}` : "Order details"}
          </DialogTitle>
        </DialogHeader>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError || !order ? (
          <p className="text-sm text-[#ba1a1a]">
            {orderErrorMessage(query.error)}
          </p>
        ) : (
          <div className="space-y-5 text-sm">
            <div className="flex flex-wrap items-center gap-3">
              <StatusBadge status={String(order.status)} />
              <span className="font-semibold tabular-nums">
                {order.grand_total} {order.currency}
              </span>
              <span className="text-[#64748b]">
                {order.created_at
                  ? new Date(order.created_at).toLocaleString()
                  : "—"}
              </span>
            </div>
            {address ? (
              <div className="rounded-xl border border-[#e2e8f0] bg-[#f8fafc] p-4">
                <p className="mb-1 font-semibold text-[#0f172a]">Shipping</p>
                <p>
                  {address.recipient_name} · {address.phone}
                </p>
                <p className="text-[#64748b]">{address.address_line}</p>
              </div>
            ) : null}
            <div>
              <p className="mb-2 font-semibold text-[#0f172a]">Line items</p>
              <ul className="divide-y divide-[#e2e8f0] rounded-xl border border-[#e2e8f0]">
                {items.map((item) => (
                  <li
                    key={item.id}
                    className="flex items-center justify-between px-4 py-2"
                  >
                    <span>
                      {item.name}{" "}
                      <span className="text-[#64748b]">×{item.qty}</span>
                    </span>
                    <span className="tabular-nums">{item.line_total}</span>
                  </li>
                ))}
              </ul>
            </div>
            {history.length > 0 ? (
              <div>
                <p className="mb-2 font-semibold text-[#0f172a]">Timeline</p>
                <ol className="space-y-2 border-l-2 border-[#ecf2ff] pl-4">
                  {history.map((row, idx) => (
                    <li key={`${row.to_status}-${idx}`}>
                      <span className="font-medium">{row.to_status}</span>
                      <span className="ml-2 text-xs text-[#64748b]">
                        {row.created_at
                          ? new Date(row.created_at).toLocaleString()
                          : ""}
                      </span>
                    </li>
                  ))}
                </ol>
              </div>
            ) : null}
            <Button asChild>
              <Link href={`/admin/orders/${order.id}`}>Open full page</Link>
            </Button>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
