"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
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
    <main className="mx-auto max-w-6xl px-4 py-10">
      <h1 className="text-2xl font-semibold">Giỏ hàng</h1>
      {error ? (
        <p className="mt-4 text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {loading ? (
        <p className="mt-6 text-sm text-zinc-600">Đang tải giỏ hàng…</p>
      ) : null}
      {empty ? (
        <div className="mt-6 space-y-4">
          <p className="text-zinc-600">Giỏ hàng trống.</p>
          <Button asChild>
            <Link href="/products">Tiếp tục xem sản phẩm</Link>
          </Button>
        </div>
      ) : null}
      {items.length > 0 ? (
        <div className="mt-8 grid gap-10 lg:grid-cols-[1fr_20rem]">
          <ul className="divide-y">
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
                <li key={item.id} className="flex gap-4 py-4">
                  <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded bg-zinc-100">
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
                    <Link href={href} className="font-medium hover:underline">
                      {name}
                    </Link>
                    {labels ? (
                      <p className="mt-1 text-sm text-zinc-500">{labels}</p>
                    ) : null}
                    <div className="mt-3 flex flex-wrap items-center gap-3">
                      <label className="text-sm text-zinc-600">
                        Số lượng
                        <Input
                          type="number"
                          min={1}
                          className="mt-1 w-20"
                          value={item.qty}
                          disabled={busyId === item.id}
                          onChange={(e) => {
                            const next = Number(e.target.value);
                            if (Number.isInteger(next) && next >= 1) {
                              void onQty(item.id, next);
                            }
                          }}
                        />
                      </label>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        disabled={busyId === item.id}
                        onClick={() => void onRemove(item.id)}
                      >
                        Xóa
                      </Button>
                    </div>
                  </div>
                  <p className="text-sm font-medium">{formatVnd(item.line_total)}</p>
                </li>
              );
            })}
          </ul>
          <aside className="h-fit rounded-lg border p-4">
            <p className="flex items-center justify-between text-sm">
              <span>Tạm tính</span>
              <span className="font-medium">{formatVnd(cart?.subtotal ?? 0)}</span>
            </p>
            <Button className="mt-4 w-full" asChild disabled={items.length === 0}>
              <Link href="/checkout">Thanh toán</Link>
            </Button>
          </aside>
        </div>
      ) : null}
    </main>
  );
}
