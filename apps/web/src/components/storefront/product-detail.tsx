"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";

import { Button } from "@/components/ui/button";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import { addCartItem } from "@/lib/api/storefront/cart";
import { formatVnd } from "@/lib/api/storefront/money";
import type { PublicProductDetail } from "@/lib/api/storefront/types";
import { emitCartChanged } from "@/lib/storefront/cart-events";

export function ProductDetail({ product }: { product: PublicProductDetail }) {
  const initialId =
    product.variants.find((v) => v.is_default)?.id ??
    product.default_variant?.id ??
    product.variants[0]?.id;

  const [selectedId, setSelectedId] = useState<number | undefined>(initialId);
  const selected =
    product.variants.find((v) => v.id === selectedId) ?? product.variants[0];

  const gallery = [...(selected?.images?.length ? selected.images : product.images)].sort(
    (a, b) => {
      const pa = a.is_primary ? 0 : 1;
      const pb = b.is_primary ? 0 : 1;
      if (pa !== pb) return pa - pb;
      return (a.position ?? 0) - (b.position ?? 0);
    },
  );

  const [active, setActive] = useState(0);
  const [pending, setPending] = useState(false);
  const [added, setAdded] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const main = gallery[Math.min(active, Math.max(gallery.length - 1, 0))];
  const mainSrc = absoluteMediaUrl(main?.url ?? main?.thumbnail_url);

  async function onAddToCart() {
    if (!selected) return;
    setPending(true);
    setError(null);
    try {
      await addCartItem(selected.id, 1);
      emitCartChanged();
      setAdded(true);
    } catch (e) {
      setAdded(false);
      setError(storefrontErrorMessage(e));
    } finally {
      setPending(false);
    }
  }

  return (
    <div className="grid gap-10 md:grid-cols-2">
      <div>
        <div className="relative aspect-square overflow-hidden rounded-lg bg-zinc-100">
          {mainSrc ? (
            <Image
              src={mainSrc}
              alt={main?.alt ?? product.name}
              fill
              priority
              sizes="(min-width: 768px) 50vw, 100vw"
              className="object-cover"
            />
          ) : (
            <div className="flex h-full items-center justify-center text-sm text-zinc-400">
              Không có ảnh
            </div>
          )}
        </div>
        {gallery.length > 1 ? (
          <ul className="mt-3 flex gap-2 overflow-x-auto">
            {gallery.map((img, i) => {
              const thumb = absoluteMediaUrl(img.thumbnail_url ?? img.url);
              if (!thumb) return null;
              return (
                <li key={`${img.url}-${i}`}>
                  <button
                    type="button"
                    onClick={() => setActive(i)}
                    className={
                      i === active
                        ? "relative h-16 w-16 overflow-hidden rounded border-2 border-zinc-950"
                        : "relative h-16 w-16 overflow-hidden rounded border"
                    }
                  >
                    <Image src={thumb} alt="" fill className="object-cover" />
                  </button>
                </li>
              );
            })}
          </ul>
        ) : null}
      </div>

      <div>
        {product.brand ? (
          <p className="text-sm text-zinc-500">{product.brand.name}</p>
        ) : null}
        <h1 className="mt-1 text-3xl font-semibold">{product.name}</h1>
        {selected ? (
          <div className="mt-4">
            <p className="text-2xl font-semibold">{formatVnd(selected.price)}</p>
            {selected.compare_at_price != null &&
            Number(selected.compare_at_price) > Number(selected.price) ? (
              <p className="text-sm text-zinc-500 line-through">
                {formatVnd(selected.compare_at_price)}
              </p>
            ) : null}
          </div>
        ) : product.default_variant ? (
          <p className="mt-4 text-2xl font-semibold">
            {formatVnd(product.default_variant.price)}
          </p>
        ) : null}

        {product.variants.length > 1 ? (
          <fieldset className="mt-6">
            <legend className="text-sm font-medium">Phiên bản</legend>
            <div className="mt-2 flex flex-wrap gap-2">
              {product.variants.map((v) => {
                const label =
                  v.attributes.map((a) => a.option.label).join(" / ") || v.sku;
                const on = v.id === selected?.id;
                return (
                  <button
                    key={v.id}
                    type="button"
                    onClick={() => {
                      setSelectedId(v.id);
                      setActive(0);
                    }}
                    className={
                      on
                        ? "rounded-md border border-zinc-950 bg-zinc-950 px-3 py-1.5 text-sm text-zinc-50"
                        : "rounded-md border px-3 py-1.5 text-sm hover:bg-zinc-50"
                    }
                  >
                    {label}
                  </button>
                );
              })}
            </div>
          </fieldset>
        ) : null}

        {product.description ? (
          <div className="mt-6 whitespace-pre-wrap text-sm leading-relaxed text-zinc-700">
            {product.description}
          </div>
        ) : null}

        <div className="mt-8 space-y-2">
          <Button
            className="w-full sm:w-auto"
            disabled={!selected || pending}
            onClick={() => void onAddToCart()}
          >
            {pending ? "Đang thêm…" : "Thêm vào giỏ"}
          </Button>
          {added ? (
            <p className="text-sm text-zinc-700">
              Đã thêm vào giỏ.{" "}
              <Link href="/cart" className="underline">
                Xem giỏ hàng
              </Link>
            </p>
          ) : null}
          {error ? (
            <p className="text-sm text-red-700" role="alert">
              {error}
            </p>
          ) : null}
        </div>
      </div>
    </div>
  );
}
