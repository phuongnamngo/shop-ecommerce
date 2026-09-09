"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { listCustomerOrders } from "@/lib/api/storefront/customer";
import { formatVnd } from "@/lib/api/storefront/money";
import type { CustomerOrder, PageMeta } from "@/lib/api/storefront/types";

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
      <h1 className="text-2xl font-semibold">Đơn hàng</h1>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {rows.length === 0 && !error ? (
        <p className="text-sm text-zinc-600">Chưa có đơn hàng.</p>
      ) : (
        <ul className="divide-y rounded-lg border">
          {rows.map((row) => (
            <li key={row.id} className="flex items-center justify-between p-3 text-sm">
              <div>
                <Link
                  href={`/account/orders/${row.id}`}
                  className="font-medium hover:underline"
                >
                  {row.number}
                </Link>
                <p className="text-zinc-500">
                  {row.status}
                  {row.created_at
                    ? ` · ${new Date(row.created_at).toLocaleString()}`
                    : ""}
                </p>
              </div>
              <span>{formatVnd(row.grand_total)}</span>
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
