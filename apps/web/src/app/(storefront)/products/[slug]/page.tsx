import type { Metadata } from "next";
import { notFound } from "next/navigation";

import { ProductDetail } from "@/components/storefront/product-detail";
import { StorefrontApiError } from "@/lib/api/storefront/client";
import { getPublicProduct } from "@/lib/api/storefront/catalog";
import type { PublicProductDetail } from "@/lib/api/storefront/types";

function isCatalogNotFound(e: unknown): boolean {
  return (
    e instanceof StorefrontApiError &&
    (e.status === 404 || e.code === "CATALOG_NOT_FOUND")
  );
}

async function loadProduct(
  slug: string,
): Promise<
  { ok: true; product: PublicProductDetail } | { ok: false; notFound: boolean }
> {
  try {
    const product = await getPublicProduct(slug);
    return { ok: true, product };
  } catch (e) {
    return { ok: false, notFound: isCatalogNotFound(e) };
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  const result = await loadProduct(slug);
  if (!result.ok) {
    return { title: result.notFound ? "Không tìm thấy" : "Sản phẩm" };
  }
  return {
    title: result.product.meta_title ?? result.product.name,
    description: result.product.meta_description ?? result.product.name,
  };
}

export default async function ProductPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  const result = await loadProduct(slug);
  if (!result.ok) {
    if (result.notFound) notFound();
    return (
      <main className="mx-auto max-w-6xl px-4 py-10">
        <h1 className="text-2xl font-semibold">Sản phẩm</h1>
        <p className="mt-4 text-sm text-red-700" role="alert">
          Không tải được sản phẩm. Thử lại sau.
        </p>
      </main>
    );
  }

  return (
    <main className="mx-auto max-w-6xl px-4 py-10">
      <ProductDetail product={result.product} />
    </main>
  );
}
