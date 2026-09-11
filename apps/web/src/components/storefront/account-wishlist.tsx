"use client";

import Image from "next/image";
import Link from "next/link";
import { useCallback, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import { formatVnd } from "@/lib/api/storefront/money";
import {
  fetchWishlist,
  removeWishlistItem,
  type Wishlist,
} from "@/lib/api/storefront/wishlist";
import { emitWishlistChanged } from "@/lib/storefront/wishlist-events";

export function AccountWishlist() {
  const [wishlist, setWishlist] = useState<Wishlist | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [pendingId, setPendingId] = useState<number | null>(null);

  const refresh = useCallback(() => {
    void fetchWishlist()
      .then(setWishlist)
      .catch((err) => setError(storefrontErrorMessage(err)));
  }, []);

  useEffect(() => {
    const frame = requestAnimationFrame(() => refresh());
    return () => cancelAnimationFrame(frame);
  }, [refresh]);

  async function onRemove(id: number) {
    setPendingId(id);
    try {
      await removeWishlistItem(id);
      emitWishlistChanged();
      refresh();
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPendingId(null);
    }
  }

  if (error && !wishlist) {
    return (
      <p className="text-sm text-red-700" role="alert">
        {error}
      </p>
    );
  }

  if (!wishlist) {
    return <p className="text-sm text-slate-500">Đang tải wishlist…</p>;
  }

  const items = wishlist.items ?? [];

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold tracking-tight">Wishlist</h1>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {items.length === 0 ? (
        <p className="text-sm text-slate-600">
          Chưa có sản phẩm.{" "}
          <Link href="/products" className="font-semibold text-blue-600">
            Tiếp tục mua sắm
          </Link>
        </p>
      ) : (
        <ul className="divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
          {items.map((item) => {
            const thumb = absoluteMediaUrl(
              item.thumbnail?.thumbnail_url ?? item.thumbnail?.url,
            );
            const href = item.product?.slug
              ? `/products/${item.product.slug}`
              : "/products";
            return (
              <li key={item.id} className="flex items-center gap-4 p-4">
                <div className="relative h-16 w-16 overflow-hidden rounded-lg bg-slate-100">
                  {thumb ? (
                    <Image src={thumb} alt="" fill className="object-cover" />
                  ) : null}
                </div>
                <div className="min-w-0 flex-1">
                  <Link href={href} className="font-medium text-slate-900">
                    {item.product?.name ?? item.sku ?? "Sản phẩm"}
                  </Link>
                  {item.sku ? (
                    <p className="text-xs text-slate-500">{item.sku}</p>
                  ) : null}
                  {item.price ? (
                    <p className="text-sm font-semibold">{formatVnd(item.price)}</p>
                  ) : null}
                </div>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  disabled={pendingId === item.id}
                  onClick={() => void onRemove(item.id)}
                >
                  Xóa
                </Button>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}
