import Image from "next/image";
import Link from "next/link";

import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import { formatVnd } from "@/lib/api/storefront/money";
import type { PublicProductListItem } from "@/lib/api/storefront/types";
import { discountPercent } from "@/lib/storefront/price";

export function ProductCard({ product }: { product: PublicProductListItem }) {
  const src = absoluteMediaUrl(
    product.primary_image?.thumbnail_url ?? product.primary_image?.url,
  );
  const price = product.default_variant?.price;
  const compare = product.default_variant?.compare_at_price;
  const off = discountPercent(price, compare);
  return (
    <Link
      href={`/products/${product.slug}`}
      className="group block overflow-hidden rounded-xl border border-slate-200 bg-white motion-safe:transition-shadow hover:shadow-[0_8px_24px_-8px_rgba(15,23,42,0.12)]"
    >
      <div className="relative aspect-[3/4] bg-slate-100">
        {src ? (
          <Image
            src={src}
            alt={product.primary_image?.alt ?? product.name}
            fill
            sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw"
            className="object-cover motion-safe:transition-transform motion-safe:duration-500 group-hover:scale-[1.03]"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-slate-400">
            Không có ảnh
          </div>
        )}
        {off ? (
          <span className="absolute left-2 top-2 rounded-full bg-red-500 px-2 py-0.5 text-[11px] font-semibold text-white">
            -{off}%
          </span>
        ) : null}
      </div>
      <div className="space-y-1 p-3">
        {product.brand ? (
          <p className="text-[11px] font-medium uppercase tracking-[0.08em] text-slate-500">
            {product.brand.name}
          </p>
        ) : null}
        <h2 className="line-clamp-2 text-sm font-medium text-slate-900">
          {product.name}
        </h2>
        <p className="flex flex-wrap items-baseline gap-2 text-[17px] font-bold text-slate-900">
          {price !== undefined && price !== null ? formatVnd(price) : "Liên hệ"}
          {off && compare != null ? (
            <span className="text-sm font-normal text-slate-400 line-through">
              {formatVnd(compare)}
            </span>
          ) : null}
        </p>
      </div>
    </Link>
  );
}
