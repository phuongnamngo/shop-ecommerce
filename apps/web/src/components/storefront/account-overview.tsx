"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

import { EmptyState } from "@/components/storefront/empty-state";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import {
  fetchCustomerMe,
  listCustomerOrders,
} from "@/lib/api/storefront/customer";
import { formatVnd } from "@/lib/api/storefront/money";
import type { CustomerOrder, CustomerProfile } from "@/lib/api/storefront/types";
import {
  orderStatusClass,
  orderStatusLabel,
} from "@/lib/storefront/order-status";

export function AccountOverview() {
  const [me, setMe] = useState<CustomerProfile | null>(null);
  const [orders, setOrders] = useState<CustomerOrder[]>([]);
  const [total, setTotal] = useState(0);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void Promise.all([fetchCustomerMe(), listCustomerOrders(1)])
        .then(([profile, res]) => {
          setMe(profile);
          setOrders(res.data.slice(0, 5));
          setTotal(res.meta.total);
        })
        .catch((err) => setError(storefrontErrorMessage(err)));
    });
    return () => cancelAnimationFrame(frame);
  }, []);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">Tài khoản của tôi</h1>
        {me ? (
          <p className="mt-1 text-sm text-slate-500">
            Xin chào, <span className="font-semibold text-slate-900">{me.name}</span>
          </p>
        ) : null}
      </div>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      <ul className="grid gap-3 sm:grid-cols-2">
        <li className="rounded-xl border border-slate-200 bg-white p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
            Đơn hàng
          </p>
          <p className="mt-2 text-3xl font-bold">{total}</p>
        </li>
        <li className="rounded-xl border border-slate-200 bg-white p-4">
          <p className="text-xs font-medium uppercase tracking-wide text-slate-400">
            Hồ sơ
          </p>
          <Link
            href="/account/profile"
            className="mt-2 inline-block text-sm font-semibold text-blue-600"
          >
            Cập nhật thông tin
          </Link>
        </li>
      </ul>
      <section className="rounded-xl border border-slate-200 bg-white p-5">
        <div className="flex items-center justify-between">
          <h2 className="font-semibold">Đơn hàng gần đây</h2>
          <Link href="/account/orders" className="text-sm font-semibold text-blue-600">
            Xem tất cả
          </Link>
        </div>
        {orders.length === 0 && !error ? (
          <div className="mt-4">
            <EmptyState
              title="Chưa có đơn hàng"
              description="Khi bạn đặt hàng, trạng thái sẽ hiện tại đây."
              actionHref="/products"
              actionLabel="Mua sắm ngay"
            />
          </div>
        ) : (
          <ul className="mt-4 divide-y divide-slate-100">
            {orders.map((row) => (
              <li key={row.id} className="flex items-center justify-between gap-3 py-3 text-sm">
                <div>
                  <Link
                    href={`/account/orders/${row.id}`}
                    className="font-semibold hover:text-blue-600"
                  >
                    {row.number}
                  </Link>
                  <p className="mt-1 text-xs text-slate-500">
                    {row.created_at
                      ? new Date(row.created_at).toLocaleDateString("vi-VN")
                      : ""}
                  </p>
                </div>
                <div className="text-right">
                  <span
                    className={`inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ${orderStatusClass(row.status)}`}
                  >
                    {orderStatusLabel(row.status)}
                  </span>
                  <p className="mt-1 font-semibold">{formatVnd(row.grand_total)}</p>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
