import Image from "next/image";
import Link from "next/link";

import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import { formatVnd } from "@/lib/api/storefront/money";
import type { PublicProductListItem } from "@/lib/api/storefront/types";

export function ProductCard({ product }: { product: PublicProductListItem }) {
  const src = absoluteMediaUrl(
    product.primary_image?.thumbnail_url ?? product.primary_image?.url,
  );
  const price = product.default_variant?.price;

  return (
    <Link
      href={`/products/${product.slug}`}
      className="group block overflow-hidden rounded-lg border bg-white"
    >
      <div className="relative aspect-square bg-zinc-100">
        {src ? (
          <Image
            src={src}
            alt={product.primary_image?.alt ?? product.name}
            fill
            sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
            className="object-cover transition group-hover:scale-[1.02]"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-zinc-400">
            Không có ảnh
          </div>
        )}
      </div>
      <div className="space-y-1 p-3">
        <h2 className="line-clamp-2 text-sm font-medium">{product.name}</h2>
        {product.brand ? (
          <p className="text-xs text-zinc-500">{product.brand.name}</p>
        ) : null}
        <p className="text-sm font-semibold">
          {price !== undefined && price !== null ? formatVnd(price) : "Liên hệ"}
        </p>
      </div>
    </Link>
  );
}
