import Link from "next/link";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { SlidersHorizontal } from "lucide-react";

import { EmptyState } from "@/components/storefront/empty-state";
import { ProductCard } from "@/components/storefront/product-card";
import { StorefrontBreadcrumb } from "@/components/storefront/storefront-breadcrumb";
import {
  listAllPublicBrands,
  listPublicCategories,
  listPublicProducts,
} from "@/lib/api/storefront/catalog";
import { findBrandBySlug, findCategoryBySlug } from "@/lib/api/storefront/resolve";
import type { PublicCategoryNode } from "@/lib/api/storefront/types";
import {
  listingCanonicalPath,
  listingHref,
  normalizeSort,
  type ListingQuery,
} from "@/lib/storefront/listing";
import { sfContainer } from "@/lib/storefront/ui";

export const dynamic = "force-dynamic";

function flattenOptions(
  nodes: PublicCategoryNode[],
  depth = 0,
): Array<{ slug: string; name: string; depth: number }> {
  return nodes.flatMap((n) => [
    { slug: n.slug, name: n.name, depth },
    ...flattenOptions(n.children ?? [], depth + 1),
  ]);
}

export async function generateMetadata({
  searchParams,
}: {
  searchParams: Promise<ListingQuery>;
}): Promise<Metadata> {
  const sp = await searchParams;
  const q = sp.q?.trim();
  const canonical = listingCanonicalPath({
    category: sp.category,
    brand: sp.brand,
  });

  const robots = q
    ? { index: false, follow: true }
    : undefined;

  try {
    if (sp.category) {
      const tree = await listPublicCategories();
      const node = findCategoryBySlug(tree, sp.category);
      if (node) {
        return {
          title: node.meta_title ?? node.name,
          description: node.meta_description ?? node.name,
          alternates: { canonical },
          robots,
        };
      }
    }
    if (sp.brand) {
      const brand = await findBrandBySlug(sp.brand);
      if (brand) {
        return {
          title: brand.name,
          description: brand.name,
          alternates: { canonical },
          robots,
        };
      }
    }
  } catch {
    // fall through to default
  }

  if (q) {
    return {
      title: `Tìm kiếm “${q}” — Watch`,
      description: `Kết quả tìm kiếm Watch cho “${q}”.`,
      alternates: { canonical },
      robots,
    };
  }

  return {
    title: "Sản phẩm — Watch",
    description: "Danh sách thời trang nam Watch. Lọc theo danh mục, thương hiệu và giá.",
    alternates: { canonical },
    robots,
  };
}

function FilterFields({
  q,
  category,
  brand,
  sort,
  categoryOptions,
  brands,
}: {
  q?: string;
  category?: string;
  brand?: string;
  sort: string;
  categoryOptions: Array<{ slug: string; name: string; depth: number }>;
  brands: Array<{ id: number; slug: string; name: string }>;
}) {
  return (
    <>
      {q ? <input type="hidden" name="q" value={q} /> : null}
      <label className="block text-sm">
        <span className="mb-1.5 block font-semibold text-slate-900">Danh mục</span>
        <select
          name="category"
          defaultValue={category ?? ""}
          className="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"
        >
          <option value="">Tất cả</option>
          {categoryOptions.map((c) => (
            <option key={c.slug} value={c.slug}>
              {"— ".repeat(c.depth)}
              {c.name}
            </option>
          ))}
        </select>
      </label>
      <label className="mt-4 block text-sm">
        <span className="mb-1.5 block font-semibold text-slate-900">Thương hiệu</span>
        <select
          name="brand"
          defaultValue={brand ?? ""}
          className="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"
        >
          <option value="">Tất cả</option>
          {brands.map((b) => (
            <option key={b.id} value={b.slug}>
              {b.name}
            </option>
          ))}
        </select>
      </label>
      <label className="mt-4 block text-sm">
        <span className="mb-1.5 block font-semibold text-slate-900">Sắp xếp</span>
        <select
          name="sort"
          defaultValue={sort}
          className="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm"
        >
          <option value="newest">Mới nhất</option>
          <option value="price_asc">Giá tăng dần</option>
          <option value="price_desc">Giá giảm dần</option>
        </select>
      </label>
      <button
        type="submit"
        className="mt-4 h-11 w-full rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700"
      >
        Áp dụng
      </button>
    </>
  );
}

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<ListingQuery>;
}) {
  const sp = await searchParams;
  const q = sp.q?.trim() || undefined;
  const sort = normalizeSort(sp.sort);
  const page = Math.max(1, Number(sp.page) || 1);

  let tree: PublicCategoryNode[] = [];
  try {
    tree = await listPublicCategories();
  } catch {
    return (
      <main className={`${sfContainer} py-12`}>
        <h1 className="text-2xl font-semibold">Sản phẩm</h1>
        <p className="mt-4 text-sm text-red-700" role="alert">
          Không tải được danh mục. Thử lại sau.
        </p>
      </main>
    );
  }

  if (sp.category && !findCategoryBySlug(tree, sp.category)) {
    notFound();
  }

  let brandId: number | undefined;
  let brandsError = false;
  let brands: Awaited<ReturnType<typeof listAllPublicBrands>> = [];
  try {
    brands = await listAllPublicBrands();
  } catch {
    brandsError = true;
    brands = [];
  }
  if (sp.brand) {
    if (brandsError) {
      return (
        <main className={`${sfContainer} py-12`}>
          <h1 className="text-2xl font-semibold">Sản phẩm</h1>
          <p className="mt-4 text-sm text-red-700" role="alert">
            Không tải được thương hiệu. Thử lại sau.
          </p>
        </main>
      );
    }
    const brand = brands.find((b) => b.slug === sp.brand);
    if (!brand) notFound();
    brandId = brand.id;
  }

  const categoryNode = sp.category
    ? findCategoryBySlug(tree, sp.category)
    : null;

  let error = false;
  let data: Awaited<ReturnType<typeof listPublicProducts>>["data"] = [];
  let meta = {
    current_page: page,
    per_page: 20,
    total: 0,
    last_page: 1,
  };

  try {
    const res = await listPublicProducts({
      q,
      category_id: categoryNode?.id,
      brand_id: brandId,
      sort,
      page,
      per_page: 20,
    });
    data = res.data;
    meta = res.meta;
  } catch {
    error = true;
  }

  const categoryOptions = flattenOptions(tree);
  const rest = {
    q,
    category: sp.category,
    brand: sp.brand,
    sort,
  };
  const heading = q
    ? `Kết quả cho “${q}”`
    : categoryNode?.name ?? (sp.brand ? brands.find((b) => b.slug === sp.brand)?.name : null) ?? "Sản phẩm";
  const crumbs = [
    { href: "/", label: "Trang chủ" },
    { href: "/products", label: "Sản phẩm" },
    ...(categoryNode ? [{ label: categoryNode.name }] : q ? [{ label: "Tìm kiếm" }] : []),
  ];
  const chips: Array<{ href: string; label: string }> = [];
  if (q) {
    chips.push({ href: listingHref({ ...rest, q: undefined }), label: `Tìm: ${q}` });
  }
  if (sp.category && categoryNode) {
    chips.push({
      href: listingHref({ ...rest, category: undefined }),
      label: categoryNode.name,
    });
  }
  if (sp.brand) {
    const brandName = brands.find((b) => b.slug === sp.brand)?.name ?? sp.brand;
    chips.push({
      href: listingHref({ ...rest, brand: undefined }),
      label: brandName,
    });
  }

  const filterProps = {
    q,
    category: sp.category,
    brand: sp.brand,
    sort,
    categoryOptions,
    brands,
  };

  return (
    <main className={`${sfContainer} py-8 lg:py-10`}>
      <StorefrontBreadcrumb items={crumbs} />
      <h1 className="mt-4 text-3xl font-bold tracking-tight text-slate-950">
        {heading}
      </h1>
      {categoryNode?.description ? (
        <p className="mt-2 max-w-3xl text-sm leading-relaxed text-slate-500">
          {categoryNode.description}
        </p>
      ) : q ? (
        <p className="mt-2 text-sm text-slate-500">
          {error ? "" : `${meta.total} sản phẩm phù hợp.`}
        </p>
      ) : null}

      {tree.length > 0 ? (
        <ul className="mt-6 flex gap-2 overflow-x-auto pb-1">
          <li>
            <Link
              href={listingHref({ q, brand: sp.brand, sort })}
              className={
                !sp.category
                  ? "inline-flex h-10 shrink-0 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white"
                  : "inline-flex h-10 shrink-0 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium hover:bg-slate-50"
              }
            >
              Tất cả
            </Link>
          </li>
          {tree.map((c) => (
            <li key={c.slug}>
              <Link
                href={listingHref({ q, category: c.slug, brand: sp.brand, sort })}
                className={
                  sp.category === c.slug
                    ? "inline-flex h-10 shrink-0 items-center rounded-lg border border-blue-600 bg-blue-50 px-4 text-sm font-semibold text-blue-700"
                    : "inline-flex h-10 shrink-0 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium hover:bg-slate-50"
                }
              >
                {c.name}
              </Link>
            </li>
          ))}
        </ul>
      ) : null}

      <div className="mt-8 grid gap-8 lg:grid-cols-[16rem_1fr]">
        <aside className="hidden lg:block">
          <div className="sticky top-24 rounded-xl border border-slate-200 bg-white p-4">
            <div className="mb-4 flex items-center justify-between">
              <p className="text-sm font-semibold">Bộ lọc tìm kiếm</p>
              {chips.length > 0 ? (
                <Link href="/products" className="text-xs font-semibold text-blue-600">
                  Xóa tất cả
                </Link>
              ) : null}
            </div>
            <form action="/products" method="get">
              <FilterFields {...filterProps} />
            </form>
          </div>
        </aside>

        <div>
          <details className="mb-4 rounded-xl border border-slate-200 bg-white lg:hidden">
            <summary className="flex h-12 cursor-pointer list-none items-center gap-2 px-4 text-sm font-semibold">
              <SlidersHorizontal className="h-4 w-4" />
              Bộ lọc
            </summary>
            <form action="/products" method="get" className="border-t border-slate-200 p-4">
              <FilterFields {...filterProps} />
            </form>
          </details>

          <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-sm font-medium text-slate-900">
              {error ? "" : `${meta.total} sản phẩm`}
              {chips.length > 0 ? (
                <span className="ml-2 text-blue-600">
                  Bộ lọc đang bật: {chips.length}
                </span>
              ) : null}
            </p>
            <form action="/products" method="get" className="flex items-center gap-2 text-sm">
              {q ? <input type="hidden" name="q" value={q} /> : null}
              {sp.category ? (
                <input type="hidden" name="category" value={sp.category} />
              ) : null}
              {sp.brand ? <input type="hidden" name="brand" value={sp.brand} /> : null}
              <label className="flex items-center gap-2">
                <span className="text-slate-500">Sắp xếp theo:</span>
                <select
                  name="sort"
                  defaultValue={sort}
                  className="h-10 rounded-lg border border-slate-200 bg-white px-2"
                >
                  <option value="newest">Mới nhất</option>
                  <option value="price_asc">Giá tăng dần</option>
                  <option value="price_desc">Giá giảm dần</option>
                </select>
              </label>
              <button
                type="submit"
                className="h-10 rounded-lg border border-slate-200 px-3 font-medium hover:bg-slate-50"
              >
                Áp dụng
              </button>
            </form>
          </div>

          {chips.length > 0 ? (
            <ul className="mt-3 flex flex-wrap gap-2">
              {chips.map((chip) => (
                <li key={chip.label}>
                  <Link
                    href={chip.href}
                    className="inline-flex h-8 items-center rounded-full border border-slate-200 bg-white px-3 text-xs font-medium hover:border-blue-600 hover:text-blue-700"
                  >
                    {chip.label} ×
                  </Link>
                </li>
              ))}
              <li>
                <Link href="/products" className="text-xs font-semibold text-blue-600">
                  Xóa tất cả
                </Link>
              </li>
            </ul>
          ) : null}

          {error ? (
            <p className="mt-8 text-sm text-red-700" role="alert">
              Không tải được sản phẩm. Thử lại sau.
            </p>
          ) : data.length === 0 ? (
            <div className="mt-8">
              <EmptyState
                title={q ? "Không tìm thấy sản phẩm" : "Không có sản phẩm"}
                description={
                  q
                    ? "Thử từ khóa khác hoặc xóa bộ lọc để xem toàn bộ cửa hàng."
                    : "Danh mục này chưa có sản phẩm hiển thị."
                }
                actionHref="/products"
                actionLabel="Xem tất cả sản phẩm"
              />
            </div>
          ) : (
            <ul className="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4">
              {data.map((p) => (
                <li key={p.id}>
                  <ProductCard product={p} />
                </li>
              ))}
            </ul>
          )}

          {!error && meta.last_page > 1 ? (
            <nav
              className="mt-10 flex flex-wrap items-center justify-center gap-2 text-sm"
              aria-label="Phân trang"
            >
              {meta.current_page > 1 ? (
                <Link
                  href={listingHref({ ...rest, page: meta.current_page - 1 })}
                  className="inline-flex h-10 items-center rounded-lg border border-slate-200 px-3 hover:bg-slate-50"
                >
                  Trước
                </Link>
              ) : (
                <span className="inline-flex h-10 items-center rounded-lg border border-slate-100 px-3 text-slate-300">
                  Trước
                </span>
              )}
              {Array.from({ length: meta.last_page }, (_, i) => i + 1)
                .filter((n) => {
                  if (meta.last_page <= 7) return true;
                  if (n === 1 || n === meta.last_page) return true;
                  return Math.abs(n - meta.current_page) <= 1;
                })
                .reduce<number[]>((acc, n) => {
                  if (acc.length && n - (acc[acc.length - 1] ?? n) > 1) acc.push(-n);
                  acc.push(n);
                  return acc;
                }, [])
                .map((n) =>
                  n < 0 ? (
                    <span key={`e${n}`} className="px-1 text-slate-400">
                      …
                    </span>
                  ) : (
                    <Link
                      key={n}
                      href={listingHref({ ...rest, page: n })}
                      aria-current={n === meta.current_page ? "page" : undefined}
                      className={
                        n === meta.current_page
                          ? "inline-flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white"
                          : "inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50"
                      }
                    >
                      {n}
                    </Link>
                  ),
                )}
              {meta.current_page < meta.last_page ? (
                <Link
                  href={listingHref({ ...rest, page: meta.current_page + 1 })}
                  className="inline-flex h-10 items-center rounded-lg border border-slate-200 px-3 hover:bg-slate-50"
                >
                  Sau
                </Link>
              ) : (
                <span className="inline-flex h-10 items-center rounded-lg border border-slate-100 px-3 text-slate-300">
                  Sau
                </span>
              )}
            </nav>
          ) : null}
        </div>
      </div>
    </main>
  );
}
