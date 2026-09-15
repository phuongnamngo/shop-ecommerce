"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";

import { EmptyState } from "@/components/storefront/empty-state";
import { Button } from "@/components/ui/button";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import {
  listCustomerNotifications,
  markAllCustomerNotificationsRead,
  markCustomerNotificationRead,
} from "@/lib/api/storefront/notifications";
import type { CustomerNotification, PageMeta } from "@/lib/api/storefront/types";
import { emitNotificationChanged } from "@/lib/storefront/notification-events";

export function AccountNotifications() {
  const router = useRouter();
  const [rows, setRows] = useState<CustomerNotification[]>([]);
  const [meta, setMeta] = useState<(PageMeta & { unread_count: number }) | null>(
    null,
  );
  const [page, setPage] = useState(1);
  const [error, setError] = useState<string | null>(null);
  const [markingAll, setMarkingAll] = useState(false);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void listCustomerNotifications(page, 15)
        .then((res) => {
          setRows(res.data);
          setMeta(res.meta);
        })
        .catch((err) => setError(storefrontErrorMessage(err)));
    });
    return () => cancelAnimationFrame(frame);
  }, [page]);

  async function onOpen(item: CustomerNotification) {
    try {
      await markCustomerNotificationRead(item.id);
      emitNotificationChanged();
    } catch {
      // Still navigate; missing order uses the existing order 404 page.
    }
    router.push(`/account/orders/${item.order_id}`);
  }

  async function onMarkAll() {
    setMarkingAll(true);
    try {
      await markAllCustomerNotificationsRead();
      emitNotificationChanged();
      const res = await listCustomerNotifications(page, 15);
      setRows(res.data);
      setMeta(res.meta);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setMarkingAll(false);
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="text-2xl font-bold tracking-tight">Thông báo</h1>
        {meta && meta.unread_count > 0 ? (
          <Button
            type="button"
            variant="outline"
            className="h-11"
            disabled={markingAll}
            onClick={() => void onMarkAll()}
          >
            Đánh dấu tất cả
          </Button>
        ) : null}
      </div>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {rows.length === 0 && !error ? (
        <EmptyState title="Chưa có thông báo." />
      ) : (
        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
          {rows.map((row) => (
            <li key={row.id}>
              <button
                type="button"
                className="flex w-full flex-col items-start p-4 text-left text-sm hover:bg-slate-50"
                onClick={() => void onOpen(row)}
              >
                <span
                  className={
                    row.read_at
                      ? "text-slate-700"
                      : "font-semibold text-slate-950"
                  }
                >
                  {row.title}
                </span>
                <span className="mt-1 text-slate-500">
                  {row.created_at
                    ? new Date(row.created_at).toLocaleString("vi-VN")
                    : ""}
                </span>
              </button>
            </li>
          ))}
        </ul>
      )}
      {meta && meta.last_page > 1 ? (
        <div className="flex gap-2">
          <Button
            type="button"
            variant="outline"
            disabled={page <= 1}
            onClick={() => setPage((p) => Math.max(1, p - 1))}
          >
            Trước
          </Button>
          <Button
            type="button"
            variant="outline"
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
