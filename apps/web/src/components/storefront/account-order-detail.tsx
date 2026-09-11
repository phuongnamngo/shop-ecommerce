"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";

import { StorefrontBrowserError, storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { fetchCustomerOrder } from "@/lib/api/storefront/customer";
import { formatVnd } from "@/lib/api/storefront/money";
import type { CustomerOrder } from "@/lib/api/storefront/types";
import {
  orderStatusClass,
  orderStatusLabel,
} from "@/lib/storefront/order-status";

export function AccountOrderDetail() {
  const params = useParams<{ id: string }>();
  const [order, setOrder] = useState<CustomerOrder | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      const id = Number(params.id);
      if (!Number.isInteger(id)) {
        setError("Không tìm thấy.");
        return;
      }
      void fetchCustomerOrder(id)
        .then(setOrder)
        .catch((err) => {
          if (err instanceof StorefrontBrowserError && err.status === 404) {
            setError("Không tìm thấy.");
            return;
          }
          setError(storefrontErrorMessage(err));
        });
    });
    return () => cancelAnimationFrame(frame);
  }, [params.id]);

  if (error) {
    return (
      <div className="space-y-4">
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
        <Link href="/account/orders" className="text-sm underline">
          Về danh sách đơn
        </Link>
      </div>
    );
  }

  if (!order) {
    return <p className="text-sm text-slate-500">Đang tải đơn hàng…</p>;
  }

  const items = order.items ?? [];
  const address = order.shipping_address;
  const history = order.status_history ?? [];

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold tracking-tight">{order.number}</h1>
        <p className="mt-2">
          <span
            className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${orderStatusClass(order.status)}`}
          >
            {orderStatusLabel(order.status)}
          </span>
        </p>
      </div>
      {address ? (
        <div className="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
          <p className="font-semibold text-slate-900">Giao tới</p>
          <p className="mt-1">
            {address.recipient_name} · {address.phone}
          </p>
          <p>{address.address_line}</p>
        </div>
      ) : null}
      {history.length > 0 ? (
        <ol className="space-y-3 border-l-2 border-slate-200 pl-4 text-sm">
          {history.map((step, index) => (
            <li key={`${step.to_status}-${index}`}>
              <p className="font-medium">{orderStatusLabel(step.to_status)}</p>
              <p className="text-xs text-slate-500">
                {step.created_at
                  ? new Date(step.created_at).toLocaleString("vi-VN")
                  : ""}
              </p>
            </li>
          ))}
        </ol>
      ) : null}
      <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white text-sm">
        {items.map((item, index) => (
          <li key={item.id ?? index} className="flex justify-between gap-4 p-4">
            <span>
              {item.name} {item.sku ? `(${item.sku})` : ""} × {item.qty}
              {["shipped", "completed"].includes(order.status) && item.product_slug ? (
                <>
                  {" "}
                  <Link
                    href={`/products/${item.product_slug}#reviews`}
                    className="font-semibold text-blue-600"
                  >
                    Viết đánh giá
                  </Link>
                </>
              ) : null}
            </span>
            <span className="font-semibold">{formatVnd(item.line_total)}</span>
          </li>
        ))}
      </ul>
      <div className="max-w-xs space-y-1 text-sm">
        <p className="flex justify-between">
          <span>Tạm tính</span>
          <span>{formatVnd(order.subtotal)}</span>
        </p>
        <p className="flex justify-between">
          <span>Giảm giá</span>
          <span>{formatVnd(order.discount_total)}</span>
        </p>
        <p className="flex justify-between">
          <span>Vận chuyển</span>
          <span>{formatVnd(order.shipping_total)}</span>
        </p>
        <p className="flex justify-between">
          <span>Thuế</span>
          <span>{formatVnd(order.tax_total)}</span>
        </p>
        <p className="flex justify-between font-semibold">
          <span>Tổng</span>
          <span>{formatVnd(order.grand_total)}</span>
        </p>
      </div>
      <Link href="/account/orders" className="text-sm font-semibold text-blue-600">
        Về danh sách đơn
      </Link>
    </div>
  );
}
