"use client";

import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import { Check, RotateCcw, Truck } from "lucide-react";

import { QuantitySelector } from "@/components/storefront/quantity-selector";
import { Button } from "@/components/ui/button";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import { addCartItem } from "@/lib/api/storefront/cart";
import { formatVnd } from "@/lib/api/storefront/money";
import type { PublicProductDetail } from "@/lib/api/storefront/types";
import { emitCartChanged } from "@/lib/storefront/cart-events";
import { discountPercent } from "@/lib/storefront/price";
import { cn } from "@/lib/utils";

export function ProductDetail({ product }: { product: PublicProductDetail }) {
  const router = useRouter();
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
  const [qty, setQty] = useState(1);
  const [pending, setPending] = useState(false);
  const [added, setAdded] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [openInfo, setOpenInfo] = useState(true);
  const main = gallery[Math.min(active, Math.max(gallery.length - 1, 0))];
  const mainSrc = absoluteMediaUrl(main?.url ?? main?.thumbnail_url);
  const off = discountPercent(selected?.price, selected?.compare_at_price);

  const attrGroups = useMemo(() => {
    const names = new Map<string, { name: string; options: string[] }>();
    for (const v of product.variants) {
      for (const a of v.attributes) {
        const key = a.slug ?? a.name ?? "option";
        const label = a.option.label;
        const existing = names.get(key);
        if (!existing) {
          names.set(key, { name: a.name ?? key, options: [label] });
        } else if (!existing.options.includes(label)) {
          existing.options.push(label);
        }
      }
    }
    return [...names.entries()];
  }, [product.variants]);

  function selectByAttribute(slug: string, label: string) {
    const current = selected;
    const desired = new Map(
      (current?.attributes ?? []).map((a) => [a.slug ?? a.name ?? "option", a.option.label]),
    );
    desired.set(slug, label);
    const match =
      product.variants.find((v) =>
        [...desired.entries()].every(([key, value]) =>
          v.attributes.some(
            (a) => (a.slug ?? a.name ?? "option") === key && a.option.label === value,
          ),
        ),
      ) ??
      product.variants.find((v) =>
        v.attributes.some(
          (a) => (a.slug ?? a.name ?? "option") === slug && a.option.label === label,
        ),
      );
    if (match) {
      setSelectedId(match.id);
      setActive(0);
    }
  }

  async function onAddToCart() {
    if (!selected) return;
    setPending(true);
    setError(null);
    try {
      await addCartItem(selected.id, qty);
      emitCartChanged();
      setAdded(true);
    } catch (e) {
      setAdded(false);
      setError(storefrontErrorMessage(e));
    } finally {
      setPending(false);
    }
  }

  async function onBuyNow() {
    if (!selected) return;
    setPending(true);
    setError(null);
    try {
      await addCartItem(selected.id, qty);
      emitCartChanged();
      router.push("/checkout");
    } catch (e) {
      setError(storefrontErrorMessage(e));
    } finally {
      setPending(false);
    }
  }

  const selectedAttr = (slug: string) =>
    selected?.attributes.find((a) => (a.slug ?? a.name ?? "option") === slug)
      ?.option.label;

  return (
    <div className="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
      <div>
        <div className="relative aspect-[3/4] overflow-hidden rounded-xl bg-slate-100">
          {mainSrc ? (
            <Image
              src={mainSrc}
              alt={main?.alt ?? product.name}
              fill
              priority
              sizes="(min-width: 1024px) 50vw, 100vw"
              className="object-cover"
            />
          ) : (
            <div className="flex h-full items-center justify-center text-sm text-slate-400">
              Không có ảnh
            </div>
          )}
          {off ? (
            <span className="absolute left-3 top-3 rounded-full bg-red-500 px-2.5 py-1 text-xs font-semibold text-white">
              -{off}%
            </span>
          ) : null}
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
                    aria-label={`Ảnh ${i + 1}`}
                    className={cn(
                      "relative h-20 w-16 overflow-hidden rounded-lg border",
                      i === active ? "border-blue-600 ring-2 ring-blue-600/20" : "border-slate-200",
                    )}
                  >
                    <Image src={thumb} alt="" fill className="object-cover" />
                  </button>
                </li>
              );
            })}
          </ul>
        ) : null}
      </div>

      <div className="lg:sticky lg:top-24 lg:self-start">
        {product.brand ? (
          <p className="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
            {product.brand.name}
          </p>
        ) : null}
        <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">
          {product.name}
        </h1>
        {selected ? (
          <div className="mt-4 flex flex-wrap items-baseline gap-3">
            <p className="text-[26px] font-bold text-slate-950">
              {formatVnd(selected.price)}
            </p>
            {off && selected.compare_at_price != null ? (
              <p className="text-sm text-slate-400 line-through">
                {formatVnd(selected.compare_at_price)}
              </p>
            ) : null}
            {off ? (
              <span className="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">
                -{off}%
              </span>
            ) : null}
          </div>
        ) : product.default_variant ? (
          <p className="mt-4 text-[26px] font-bold">
            {formatVnd(product.default_variant.price)}
          </p>
        ) : null}

        {attrGroups.length > 0 ? (
          <div className="mt-6 space-y-4">
            {attrGroups.map(([slug, group]) => (
              <fieldset key={slug}>
                <legend className="text-sm font-semibold">
                  {group.name}
                  {selectedAttr(slug) ? (
                    <span className="ml-2 font-normal text-slate-500">
                      {selectedAttr(slug)}
                    </span>
                  ) : null}
                </legend>
                <div className="mt-2 flex flex-wrap gap-2">
                  {group.options.map((opt) => {
                    const on = selectedAttr(slug) === opt;
                    return (
                      <button
                        key={opt}
                        type="button"
                        onClick={() => selectByAttribute(slug, opt)}
                        className={cn(
                          "min-h-11 min-w-11 rounded-lg border px-3 text-sm font-medium",
                          on
                            ? "border-blue-600 bg-blue-50 text-blue-700"
                            : "border-slate-200 bg-white hover:border-slate-300",
                        )}
                      >
                        {opt}
                      </button>
                    );
                  })}
                </div>
              </fieldset>
            ))}
          </div>
        ) : product.variants.length > 1 ? (
          <fieldset className="mt-6">
            <legend className="text-sm font-semibold">Phiên bản</legend>
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
                        ? "min-h-11 rounded-lg border border-blue-600 bg-blue-50 px-3 text-sm font-medium text-blue-700"
                        : "min-h-11 rounded-lg border border-slate-200 px-3 text-sm hover:bg-slate-50"
                    }
                  >
                    {label}
                  </button>
                );
              })}
            </div>
          </fieldset>
        ) : null}

        <div className="mt-6">
          <p className="text-sm font-semibold">Số lượng</p>
          <div className="mt-2">
            <QuantitySelector value={qty} onChange={setQty} disabled={pending} />
          </div>
        </div>

        <div className="mt-6 hidden gap-3 sm:flex">
          <Button
            className="h-12 flex-1 rounded-lg bg-blue-600 text-sm font-semibold hover:bg-blue-700"
            disabled={!selected || pending}
            onClick={() => void onAddToCart()}
          >
            {pending ? "Đang thêm…" : "Thêm vào giỏ"}
          </Button>
          <Button
            variant="outline"
            className="h-12 flex-1 rounded-lg border-blue-600 text-blue-700 hover:bg-blue-50"
            disabled={!selected || pending}
            onClick={() => void onBuyNow()}
          >
            Mua ngay
          </Button>
        </div>
        {added ? (
          <p className="mt-3 text-sm text-emerald-700">
            Đã thêm vào giỏ.{" "}
            <Link href="/cart" className="font-semibold underline">
              Xem giỏ hàng
            </Link>
          </p>
        ) : null}
        {error ? (
          <p className="mt-3 text-sm text-red-700" role="alert">
            {error}
          </p>
        ) : null}

        <ul className="mt-6 space-y-2 text-sm text-slate-600">
          <li className="flex gap-2">
            <Truck className="h-4 w-4 text-blue-600" />
            Giao hàng toàn quốc, chọn phương thức lúc thanh toán
          </li>
          <li className="flex gap-2">
            <RotateCcw className="h-4 w-4 text-blue-600" />
            Đổi trả trong 30 ngày
          </li>
          <li className="flex gap-2">
            <Check className="h-4 w-4 text-blue-600" />
            Hàng chính hãng, giá lấy từ hệ thống
          </li>
        </ul>

        {product.description ? (
          <div className="mt-6 border-t border-slate-200">
            <button
              type="button"
              className="flex w-full items-center justify-between py-4 text-left text-sm font-semibold"
              onClick={() => setOpenInfo((v) => !v)}
              aria-expanded={openInfo}
            >
              Chi tiết sản phẩm
              <span aria-hidden>{openInfo ? "−" : "+"}</span>
            </button>
            {openInfo ? (
              <div className="whitespace-pre-wrap pb-4 text-sm leading-relaxed text-slate-600">
                {product.description}
              </div>
            ) : null}
          </div>
        ) : null}
      </div>

      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white p-3 sm:hidden" style={{ paddingBottom: "calc(0.75rem + env(safe-area-inset-bottom))" }}>
        <div className="flex items-center gap-3">
          <div className="min-w-0">
            <p className="truncate text-sm font-bold">
              {selected ? formatVnd(selected.price) : "—"}
            </p>
          </div>
          <Button
            className="h-11 flex-1 rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
            disabled={!selected || pending}
            onClick={() => void onAddToCart()}
          >
            {pending ? "Đang thêm…" : "Thêm vào giỏ"}
          </Button>
        </div>
      </div>
    </div>
  );
}
