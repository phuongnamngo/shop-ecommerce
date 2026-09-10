"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";

import { EmptyState } from "@/components/storefront/empty-state";
import { QuantitySelector } from "@/components/storefront/quantity-selector";
import { StorefrontBreadcrumb } from "@/components/storefront/storefront-breadcrumb";
import { Button } from "@/components/ui/button";
import {
  StorefrontBrowserError,
  storefrontErrorMessage,
  storefrontMediaUrl,
} from "@/lib/api/storefront/browser";
import {
  fetchActiveCart,
  removeCartItem,
  updateCartItem,
} from "@/lib/api/storefront/cart";
import { formatVnd } from "@/lib/api/storefront/money";
import type { StorefrontCart } from "@/lib/api/storefront/types";
import { sfContainer } from "@/lib/storefront/ui";

export function CartPage() {
  const [cart, setCart] = useState<StorefrontCart | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);

  const load = useCallback(async () => {
    await Promise.resolve();
    setError(null);
    try {
      setCart(await fetchActiveCart());
    } catch (e) {
      if (e instanceof StorefrontBrowserError && e.code === "CART_INVALID_TOKEN") {
        setCart(null);
      } else {
        setError(storefrontErrorMessage(e));
      }
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void load();
    });
    return () => cancelAnimationFrame(frame);
  }, [load]);

  async function onQty(itemId: number, qty: number) {
    if (qty < 1) return;
    setBusyId(itemId);
    setError(null);
    try {
      setCart(await updateCartItem(itemId, qty));
    } catch (e) {
      setError(storefrontErrorMessage(e));
    } finally {
      setBusyId(null);
    }
  }

  async function onRemove(itemId: number) {
    setBusyId(itemId);
    setError(null);
    try {
      await removeCartItem(itemId);
      await load();
    } catch (e) {
      setError(storefrontErrorMessage(e));
    } finally {
      setBusyId(null);
    }
  }

  const items = cart?.items ?? [];
  const empty = !loading && items.length === 0;

  return (
    <main className={`${sfContainer} py-8 lg:py-10`}>
      <StorefrontBreadcrumb
        items={[
          { href: "/", label: "Trang chủ" },
          { label: "Giỏ hàng của bạn" },
        ]}
      />
      <h1 className="mt-4 text-3xl font-bold tracking-tight">Giỏ hàng</h1>
      {error ? (
        <p className="mt-4 text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {loading ? (
        <p className="mt-6 text-sm text-slate-500">Đang tải giỏ hàng…</p>
      ) : null}
      {empty ? (
        <div className="mt-8">
          <EmptyState
            title="Giỏ hàng trống"
            description="Thêm sản phẩm từ cửa hàng để tiến hành thanh toán."
            actionHref="/products"
            actionLabel="Tiếp tục xem sản phẩm"
          />
        </div>
      ) : null}
      {items.length > 0 ? (
        <div className="mt-8 grid gap-8 lg:grid-cols-[1fr_22rem]">
          <ul className="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
            {items.map((item) => {
              const thumb = storefrontMediaUrl(
                item.thumbnail?.thumbnail_url ?? item.thumbnail?.url,
              );
              const name = item.product?.name ?? item.sku ?? "Sản phẩm";
              const href = item.product?.slug
                ? `/products/${item.product.slug}`
                : "/products";
              const labels = item.attributes
                .map((a) => a.option.label)
                .filter(Boolean)
                .join(" / ");
              return (
                <li key={item.id} className="flex gap-4 p-4">
                  <div className="relative h-28 w-24 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                    {thumb ? (
                      <Image
                        src={thumb}
                        alt={item.thumbnail?.alt ?? name}
                        fill
                        sizes="96px"
                        className="object-cover"
                      />
                    ) : null}
                  </div>
                  <div className="min-w-0 flex-1">
                    <Link href={href} className="font-semibold hover:text-blue-600">
                      {name}
                    </Link>
                    {labels ? (
                      <p className="mt-1 text-sm text-slate-500">{labels}</p>
                    ) : null}
                    <div className="mt-3 flex flex-wrap items-center gap-3">
                      <QuantitySelector
                        value={item.qty}
                        disabled={busyId === item.id}
                        onChange={(next) => void onQty(item.id, next)}
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        className="text-slate-500"
                        disabled={busyId === item.id}
                        onClick={() => void onRemove(item.id)}
                      >
                        Xóa
                      </Button>
                    </div>
                  </div>
                  <p className="text-sm font-bold">{formatVnd(item.line_total)}</p>
                </li>
              );
            })}
          </ul>
          <aside className="h-fit rounded-xl border border-slate-200 bg-white p-5 lg:sticky lg:top-24">
            <h2 className="font-semibold">Tóm tắt đơn hàng</h2>
            <p className="mt-4 flex items-center justify-between text-sm">
              <span className="text-slate-500">Tạm tính</span>
              <span className="font-semibold">{formatVnd(cart?.subtotal ?? 0)}</span>
            </p>
            <p className="mt-2 text-xs text-slate-400">
              Phí vận chuyển được tính ở bước thanh toán.
            </p>
            <Button
              className="mt-5 h-12 w-full rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
              asChild
              disabled={items.length === 0}
            >
              <Link href="/checkout">Tiến hành thanh toán</Link>
            </Button>
            <Link
              href="/products"
              className="mt-3 block text-center text-sm font-medium text-slate-600 hover:text-blue-600"
            >
              Tiếp tục mua sắm
            </Link>
          </aside>
        </div>
      ) : null}
    </main>
  );
}
