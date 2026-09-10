"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetTitle,
} from "@/components/ui/sheet";
import {
  StorefrontBrowserError,
  storefrontErrorMessage,
  storefrontMediaUrl,
} from "@/lib/api/storefront/browser";
import { fetchActiveCart } from "@/lib/api/storefront/cart";
import { formatVnd } from "@/lib/api/storefront/money";
import type { StorefrontCart } from "@/lib/api/storefront/types";
import { CART_CHANGED_EVENT } from "@/lib/storefront/cart-events";

export function MiniCartDrawer({
  open,
  onOpenChange,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}) {
  const [cart, setCart] = useState<StorefrontCart | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const load = useCallback(async () => {
    await Promise.resolve();
    setError(null);
    setLoading(true);
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
    if (!open) return;
    const frame = requestAnimationFrame(() => {
      void load();
    });
    return () => cancelAnimationFrame(frame);
  }, [open, load]);

  useEffect(() => {
    const onChange = () => {
      if (open) void load();
    };
    window.addEventListener(CART_CHANGED_EVENT, onChange);
    return () => window.removeEventListener(CART_CHANGED_EVENT, onChange);
  }, [open, load]);

  const items = cart?.items ?? [];

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent
        side="right"
        className="w-full max-w-md bg-white text-slate-900"
      >
        <SheetTitle>Giỏ hàng</SheetTitle>
        {error ? (
          <p className="text-sm text-red-700" role="alert">
            {error}
          </p>
        ) : null}
        {loading ? (
          <p className="text-sm text-slate-500">Đang tải…</p>
        ) : null}
        {!loading && items.length === 0 ? (
          <div className="space-y-4 pt-4">
            <p className="text-sm text-slate-600">Giỏ hàng trống.</p>
            <Button asChild className="h-11 w-full rounded-lg bg-blue-600 hover:bg-blue-700">
              <Link href="/products" onClick={() => onOpenChange(false)}>
                Tiếp tục mua sắm
              </Link>
            </Button>
          </div>
        ) : null}
        {items.length > 0 ? (
          <div className="flex min-h-0 flex-1 flex-col">
            <ul className="flex-1 space-y-4 overflow-y-auto py-4">
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
                  <li key={item.id} className="flex gap-3">
                    <div className="relative h-20 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                      {thumb ? (
                        <Image
                          src={thumb}
                          alt={item.thumbnail?.alt ?? name}
                          fill
                          sizes="64px"
                          className="object-cover"
                        />
                      ) : null}
                    </div>
                    <div className="min-w-0 flex-1">
                      <Link
                        href={href}
                        className="line-clamp-2 text-sm font-medium hover:text-blue-600"
                        onClick={() => onOpenChange(false)}
                      >
                        {name}
                      </Link>
                      {labels ? (
                        <p className="mt-0.5 text-xs text-slate-500">{labels}</p>
                      ) : null}
                      <p className="mt-1 text-sm font-semibold">
                        {formatVnd(item.line_total)}
                      </p>
                      <p className="text-xs text-slate-500">SL: {item.qty}</p>
                    </div>
                  </li>
                );
              })}
            </ul>
            <div className="space-y-3 border-t border-slate-200 pt-4">
              <p className="flex justify-between text-sm">
                <span className="text-slate-500">Tạm tính</span>
                <span className="font-semibold">
                  {formatVnd(cart?.subtotal ?? 0)}
                </span>
              </p>
              <Button
                asChild
                className="h-11 w-full rounded-lg bg-blue-600 hover:bg-blue-700"
              >
                <Link href="/checkout" onClick={() => onOpenChange(false)}>
                  Thanh toán
                </Link>
              </Button>
              <Button variant="outline" asChild className="h-11 w-full rounded-lg">
                <Link href="/cart" onClick={() => onOpenChange(false)}>
                  Xem giỏ hàng
                </Link>
              </Button>
            </div>
          </div>
        ) : null}
      </SheetContent>
    </Sheet>
  );
}
