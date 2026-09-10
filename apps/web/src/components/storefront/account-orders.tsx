"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { EmptyState } from "@/components/storefront/empty-state";
import { Button } from "@/components/ui/button";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { listCustomerOrders } from "@/lib/api/storefront/customer";
import { formatVnd } from "@/lib/api/storefront/money";
import type { CustomerOrder, PageMeta } from "@/lib/api/storefront/types";
import {
  orderStatusClass,
  orderStatusLabel,
} from "@/lib/storefront/order-status";

export function AccountOrders() {
  const [rows, setRows] = useState<CustomerOrder[]>([]);
  const [meta, setMeta] = useState<PageMeta | null>(null);
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void listCustomerOrders(page)
        .then((res) => {
          setRows(res.data);
          setMeta(res.meta);
        })
        .catch((err) => setError(storefrontErrorMessage(err)));
    });
    return () => cancelAnimationFrame(frame);
  }, [page]);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold tracking-tight">Đơn hàng của tôi</h1>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {rows.length === 0 && !error ? (
        <EmptyState
          title="Chưa có đơn hàng"
          description="Các đơn bạn đặt sẽ được liệt kê tại đây."
          actionHref="/products"
          actionLabel="Mua sắm ngay"
        />
      ) : (
        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
          {rows.map((row) => (
            <li key={row.id} className="flex items-center justify-between p-4 text-sm">
              <div>
                <Link
                  href={`/account/orders/${row.id}`}
                  className="font-semibold hover:text-blue-600"
                >
                  {row.number}
                </Link>
                <p className="mt-1 text-slate-500">
                  <span
                    className={`mr-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ${orderStatusClass(row.status)}`}
                  >
                    {orderStatusLabel(row.status)}
                  </span>
                  {row.created_at
                    ? new Date(row.created_at).toLocaleString("vi-VN")
                    : ""}
                </p>
              </div>
              <span className="font-semibold">{formatVnd(row.grand_total)}</span>
            </li>
          ))}
        </ul>
      )}
      {meta && meta.last_page > 1 ? (
        <div className="flex gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
          >
            Trước
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={page >= meta.last_page}
            onClick={() => setPage((p) => p + 1)}
          >
            Sau
          </Button>
        </div>
      ) : null}
    </div>
  );
}
