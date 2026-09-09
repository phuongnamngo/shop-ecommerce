"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";

import { StorefrontBrowserError, storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { fetchCustomerOrder } from "@/lib/api/storefront/customer";
import { formatVnd } from "@/lib/api/storefront/money";
import type { CustomerOrder } from "@/lib/api/storefront/types";

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
    return <p className="text-sm text-zinc-600">Đang tải đơn hàng…</p>;
  }

  const items = order.items ?? [];
  const address = order.shipping_address;

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">{order.number}</h1>
      <p className="text-sm text-zinc-600">Trạng thái: {order.status}</p>
      {address ? (
        <p className="text-sm text-zinc-600">
          Giao tới: {address.recipient_name} · {address.phone} ·{" "}
          {address.address_line}
        </p>
      ) : null}
      <ul className="divide-y rounded-lg border text-sm">
        {items.map((item, index) => (
          <li key={item.id ?? index} className="flex justify-between p-3">
            <span>
              {item.name} {item.sku ? `(${item.sku})` : ""} × {item.qty}
            </span>
            <span>{formatVnd(item.line_total)}</span>
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
        <p className="flex justify-between font-medium">
          <span>Tổng</span>
          <span>{formatVnd(order.grand_total)}</span>
        </p>
      </div>
      <Link href="/account/orders" className="text-sm underline">
        Về danh sách đơn
      </Link>
    </div>
  );
}
