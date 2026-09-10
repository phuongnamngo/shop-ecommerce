import Link from "next/link";
import type { Metadata } from "next";
import { notFound } from "next/navigation";

import { ProductCard } from "@/components/storefront/product-card";
import { ProductDetail } from "@/components/storefront/product-detail";
import { StorefrontBreadcrumb } from "@/components/storefront/storefront-breadcrumb";
import { StorefrontApiError } from "@/lib/api/storefront/client";
import { getPublicProduct, listPublicProducts } from "@/lib/api/storefront/catalog";
import type { PublicProductDetail } from "@/lib/api/storefront/types";
import { sfContainer } from "@/lib/storefront/ui";

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
      <main className={`${sfContainer} py-10`}>
        <h1 className="text-2xl font-semibold">Sản phẩm</h1>
        <p className="mt-4 text-sm text-red-700" role="alert">
          Không tải được sản phẩm. Thử lại sau.
        </p>
      </main>
    );
  }

  let related: Awaited<ReturnType<typeof listPublicProducts>>["data"] = [];
  try {
    const categoryId = result.product.categories[0]?.id;
    const res = await listPublicProducts({
      category_id: categoryId,
      sort: "newest",
      per_page: 8,
      page: 1,
    });
    related = res.data.filter((p) => p.id !== result.product.id).slice(0, 4);
  } catch {
    related = [];
  }

  const crumbs = [
    { href: "/", label: "Trang chủ" },
    { href: "/products", label: "Sản phẩm" },
    ...(result.product.categories[0]
      ? [
          {
            href: `/products?category=${encodeURIComponent(result.product.categories[0].slug)}`,
            label: result.product.categories[0].name,
          },
        ]
      : []),
    { label: result.product.name },
  ];

  return (
    <main className={`${sfContainer} py-8 pb-28 sm:pb-10`}>
      <StorefrontBreadcrumb items={crumbs} />
      <div className="mt-6">
        <ProductDetail product={result.product} />
      </div>
      {related.length > 0 ? (
        <section className="mt-16">
          <div className="flex items-end justify-between">
            <h2 className="text-xl font-semibold">Có thể bạn sẽ thích</h2>
            <Link href="/products" className="text-sm font-semibold text-blue-600">
              Xem tất cả
            </Link>
          </div>
          <ul className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            {related.map((p) => (
              <li key={p.id}>
                <ProductCard product={p} />
              </li>
            ))}
          </ul>
        </section>
      ) : null}
    </main>
  );
}
