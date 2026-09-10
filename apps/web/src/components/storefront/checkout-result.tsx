"use client";

import { useEffect, useState } from "react";

import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { lookupGuestOrder } from "@/lib/api/storefront/commerce";
import { formatVnd } from "@/lib/api/storefront/money";
import type { GuestOrder } from "@/lib/api/storefront/types";
import {
  orderStatusLabel,
} from "@/lib/storefront/order-status";
import { sfContainer } from "@/lib/storefront/ui";

const STATUS_BANNER: Record<string, string> = {
  paid: "Thanh toán thành công.",
  failed: "Thanh toán không thành công.",
  cancelled: "Đơn hàng đã bị hủy.",
};

export function CheckoutResult({
  token,
  number,
  status,
}: {
  token?: string;
  number?: string;
  status?: string;
}) {
  const [order, setOrder] = useState<GuestOrder | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(Boolean(token));

  useEffect(() => {
    if (!token) {
      return;
    }
    void lookupGuestOrder(token)
      .then((data) => {
        setOrder(data);
        setError(null);
      })
      .catch((e) => {
        setOrder(null);
        setError(storefrontErrorMessage(e));
      })
      .finally(() => setLoading(false));
  }, [token]);

  const banner =
    status && STATUS_BANNER[status] ? STATUS_BANNER[status] : null;
  const shownNumber = order?.number ?? number;

  return (
    <main className={`${sfContainer} py-10`}>
      <div className="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-6 sm:p-10">
      <h1 className="text-3xl font-bold tracking-tight">
        {status === "failed" || status === "cancelled"
          ? "Kết quả đơn hàng"
          : "Đặt hàng thành công"}
      </h1>
      {banner ? (
        <p
          className={
            status === "paid"
              ? "mt-4 text-sm text-emerald-800"
              : "mt-4 text-sm text-red-700"
          }
          role="status"
        >
          {banner}
          {shownNumber ? ` Mã đơn ${shownNumber}.` : null}
        </p>
      ) : shownNumber && !order ? (
        <p className="mt-4 text-sm text-zinc-600">Mã đơn {shownNumber}.</p>
      ) : null}
      {loading ? (
        <p className="mt-6 text-sm text-zinc-600">Đang tải đơn hàng…</p>
      ) : null}
      {error ? (
        <p className="mt-6 text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {order ? (
        <div className="mt-8 space-y-6">
          <p className="text-sm text-slate-600">
            Đơn {order.number} — {orderStatusLabel(order.status)}
          </p>
          <ul className="divide-y rounded-lg border">
            {order.items.map((item, index) => (
              <li
                key={`${item.sku}-${index}`}
                className="flex justify-between gap-4 px-4 py-3 text-sm"
              >
                <div>
                  <p className="font-medium">{item.name}</p>
                  <p className="text-zinc-500">
                    {item.sku} × {item.qty}
                  </p>
                </div>
                <p>{formatVnd(item.line_total)}</p>
              </li>
            ))}
          </ul>
          {order.shipping_address ? (
            <div className="text-sm text-zinc-700">
              <p className="font-medium">Giao tới</p>
              <p>{order.shipping_address.recipient_name}</p>
              <p>{order.shipping_address.phone}</p>
              <p>{order.shipping_address.address_line}</p>
              <p>
                {order.shipping_address.ward_code},{" "}
                {order.shipping_address.district_code},{" "}
                {order.shipping_address.province_code}
              </p>
            </div>
          ) : null}
          <dl className="space-y-1 text-sm">
            <div className="flex justify-between">
              <dt>Tạm tính</dt>
              <dd>{formatVnd(order.subtotal)}</dd>
            </div>
            <div className="flex justify-between">
              <dt>Giảm giá</dt>
              <dd>{formatVnd(order.discount_total)}</dd>
            </div>
            <div className="flex justify-between">
              <dt>Vận chuyển</dt>
              <dd>{formatVnd(order.shipping_total)}</dd>
            </div>
            <div className="flex justify-between">
              <dt>Thuế</dt>
              <dd>{formatVnd(order.tax_total)}</dd>
            </div>
            <div className="flex justify-between font-medium">
              <dt>Tổng</dt>
              <dd>{formatVnd(order.grand_total)}</dd>
            </div>
          </dl>
        </div>
      ) : null}
      </div>
    </main>
  );
}
