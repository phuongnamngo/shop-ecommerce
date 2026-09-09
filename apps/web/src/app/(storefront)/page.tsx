import Link from "next/link";
import type { Metadata } from "next";

import { ProductCard } from "@/components/storefront/product-card";
import { listPublicCategories, listPublicProducts } from "@/lib/api/storefront/catalog";

export const metadata: Metadata = {
  title: "Watch — Đồng hồ",
  description: "Cửa hàng đồng hồ Watch. Xem danh mục và sản phẩm mới.",
};

export default async function HomePage() {
  let categoryError = false;
  let productError = false;
  let categories: Awaited<ReturnType<typeof listPublicCategories>> = [];
  let products: Awaited<ReturnType<typeof listPublicProducts>>["data"] = [];

  try {
    categories = await listPublicCategories();
  } catch {
    categoryError = true;
  }

  try {
    const res = await listPublicProducts({
      sort: "newest",
      per_page: 8,
      page: 1,
    });
    products = res.data;
  } catch {
    productError = true;
  }

  return (
    <main>
      <section className="bg-zinc-950 px-4 py-20 text-zinc-50">
        <div className="mx-auto max-w-6xl">
          <p className="text-sm uppercase tracking-[0.2em] text-zinc-400">
            Watch
          </p>
          <h1 className="mt-3 max-w-xl text-4xl font-semibold leading-tight">
            Đồng hồ cho nhịp sống hiện đại
          </h1>
          <p className="mt-4 max-w-lg text-zinc-300">
            Khám phá bộ sưu tập mới. Giá và tồn kho luôn lấy từ hệ thống — không
            tự tính trên trình duyệt.
          </p>
          <Link
            href="/products"
            className="mt-8 inline-flex h-11 items-center rounded-md bg-zinc-50 px-6 text-sm font-medium text-zinc-950 hover:bg-zinc-200"
          >
            Xem sản phẩm
          </Link>
        </div>
      </section>

      <section className="mx-auto max-w-6xl px-4 py-12">
        <h2 className="text-xl font-semibold">Danh mục</h2>
        {categoryError ? (
          <p className="mt-4 text-sm text-red-700" role="alert">
            Không tải được danh mục. Thử lại sau.
          </p>
        ) : categories.length === 0 ? (
          <p className="mt-4 text-sm text-zinc-600">Chưa có danh mục.</p>
        ) : (
          <ul className="mt-4 flex flex-wrap gap-2">
            {categories.map((c) => (
              <li key={c.id}>
                <Link
                  href={`/products?category=${encodeURIComponent(c.slug)}`}
                  className="inline-flex rounded-full border px-4 py-2 text-sm hover:bg-zinc-50"
                >
                  {c.name}
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="mx-auto max-w-6xl px-4 pb-16">
        <div className="flex items-end justify-between gap-4">
          <h2 className="text-xl font-semibold">Sản phẩm mới</h2>
          <Link href="/products" className="text-sm underline">
            Xem tất cả
          </Link>
        </div>
        {productError ? (
          <p className="mt-4 text-sm text-red-700" role="alert">
            Không tải được sản phẩm. Thử lại sau.
          </p>
        ) : products.length === 0 ? (
          <p className="mt-4 text-sm text-zinc-600">Chưa có sản phẩm.</p>
        ) : (
          <ul className="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
            {products.map((p) => (
              <li key={p.id}>
                <ProductCard product={p} />
              </li>
            ))}
          </ul>
        )}
      </section>
    </main>
  );
}
