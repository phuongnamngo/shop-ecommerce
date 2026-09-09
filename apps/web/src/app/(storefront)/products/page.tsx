import Link from "next/link";
import type { Metadata } from "next";
import { notFound } from "next/navigation";

import { ProductCard } from "@/components/storefront/product-card";
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

  return {
    title: "Sản phẩm — Watch",
    description: "Danh sách đồng hồ Watch. Lọc theo danh mục, thương hiệu và giá.",
    alternates: { canonical },
    robots,
  };
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
      <main className="mx-auto max-w-6xl px-4 py-12">
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
        <main className="mx-auto max-w-6xl px-4 py-12">
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

  return (
    <main className="mx-auto max-w-6xl px-4 py-10">
      <h1 className="text-2xl font-semibold">Sản phẩm</h1>

      <form
        action="/products"
        method="get"
        className="mt-6 grid gap-3 rounded-lg border bg-zinc-50 p-4 sm:grid-cols-4"
      >
        {q ? <input type="hidden" name="q" value={q} /> : null}
        <label className="text-sm">
          <span className="mb-1 block text-zinc-600">Danh mục</span>
          <select
            name="category"
            defaultValue={sp.category ?? ""}
            className="h-9 w-full rounded-md border bg-white px-2 text-sm"
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
        <label className="text-sm">
          <span className="mb-1 block text-zinc-600">Thương hiệu</span>
          <select
            name="brand"
            defaultValue={sp.brand ?? ""}
            className="h-9 w-full rounded-md border bg-white px-2 text-sm"
          >
            <option value="">Tất cả</option>
            {brands.map((b) => (
              <option key={b.id} value={b.slug}>
                {b.name}
              </option>
            ))}
          </select>
        </label>
        <label className="text-sm">
          <span className="mb-1 block text-zinc-600">Sắp xếp</span>
          <select
            name="sort"
            defaultValue={sort}
            className="h-9 w-full rounded-md border bg-white px-2 text-sm"
          >
            <option value="newest">Mới nhất</option>
            <option value="price_asc">Giá tăng dần</option>
            <option value="price_desc">Giá giảm dần</option>
          </select>
        </label>
        <div className="flex items-end">
          <button
            type="submit"
            className="h-9 w-full rounded-md bg-zinc-950 text-sm text-zinc-50"
          >
            Lọc
          </button>
        </div>
      </form>

      {q ? (
        <p className="mt-4 text-sm text-zinc-600">
          Kết quả cho “{q}”{" "}
          <Link href={listingHref({ ...rest, q: undefined })} className="underline">
            Xóa tìm kiếm
          </Link>
        </p>
      ) : null}

      {error ? (
        <p className="mt-8 text-sm text-red-700" role="alert">
          Không tải được sản phẩm. Thử lại sau.
        </p>
      ) : data.length === 0 ? (
        <p className="mt-8 text-sm text-zinc-600">Không có sản phẩm.</p>
      ) : (
        <ul className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
          {data.map((p) => (
            <li key={p.id}>
              <ProductCard product={p} />
            </li>
          ))}
        </ul>
      )}

      {!error && meta.last_page > 1 ? (
        <nav
          className="mt-10 flex flex-wrap items-center gap-3 text-sm"
          aria-label="Phân trang"
        >
          {meta.current_page > 1 ? (
            <Link
              href={listingHref({ ...rest, page: meta.current_page - 1 })}
              className="rounded-md border px-3 py-1 hover:bg-zinc-50"
            >
              Trước
            </Link>
          ) : null}
          <span>
            Trang {meta.current_page} / {meta.last_page}
          </span>
          {meta.current_page < meta.last_page ? (
            <Link
              href={listingHref({ ...rest, page: meta.current_page + 1 })}
              className="rounded-md border px-3 py-1 hover:bg-zinc-50"
            >
              Sau
            </Link>
          ) : null}
        </nav>
      ) : null}
    </main>
  );
}
