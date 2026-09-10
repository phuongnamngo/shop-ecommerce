import Image from "next/image";
import Link from "next/link";
import type { Metadata } from "next";
import {
  RefreshCw,
  ShieldCheck,
  Truck,
} from "lucide-react";

import { EmptyState } from "@/components/storefront/empty-state";
import { ProductCard } from "@/components/storefront/product-card";
import { listPublicCategories, listPublicProducts } from "@/lib/api/storefront/catalog";
import { absoluteMediaUrl } from "@/lib/api/storefront/client";
import { STORE_NAME, sfContainer } from "@/lib/storefront/ui";

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
      per_page: 12,
      page: 1,
    });
    products = res.data;
  } catch {
    productError = true;
  }

  const heroProduct = products[0];
  const heroSrc = heroProduct
    ? absoluteMediaUrl(
        heroProduct.primary_image?.url ??
          heroProduct.primary_image?.thumbnail_url,
      )
    : null;
  const look = products.slice(0, 2);
  const featured = products[0];
  const featuredSrc = featured
    ? absoluteMediaUrl(
        featured.primary_image?.url ?? featured.primary_image?.thumbnail_url,
      )
    : null;

  return (
    <main>
      <section className="bg-slate-50">
        <div
          className={`${sfContainer} grid items-center gap-8 py-10 lg:grid-cols-2 lg:py-16`}
        >
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">
              Bộ sưu tập mới
            </p>
            <h1 className="mt-3 max-w-xl text-4xl font-bold leading-tight tracking-tight text-slate-950 sm:text-[44px] sm:leading-[1.15]">
              NEW SEASON / TIME ESSENTIALS
            </h1>
            <p className="mt-4 max-w-lg text-base leading-relaxed text-slate-500">
              Đồng hồ cho nhịp sống hiện đại. Khám phá hàng mới — giá và tồn kho
              luôn lấy từ hệ thống.
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link
                href="/products"
                className="inline-flex h-12 items-center rounded-lg bg-blue-600 px-6 text-sm font-semibold text-white hover:bg-blue-700"
              >
                Xem bộ sưu tập
              </Link>
              <Link
                href="/products?sort=newest"
                className="inline-flex h-12 items-center rounded-lg border border-slate-200 bg-white px-6 text-sm font-semibold text-slate-900 hover:bg-slate-50"
              >
                Hàng mới
              </Link>
            </div>
          </div>
          <div className="relative min-h-[320px] overflow-hidden rounded-xl bg-slate-900 lg:min-h-[480px]">
            {heroSrc && heroProduct ? (
              <Image
                src={heroSrc}
                alt={heroProduct.primary_image?.alt ?? heroProduct.name}
                fill
                priority
                sizes="(min-width: 1024px) 50vw, 100vw"
                className="object-cover"
              />
            ) : (
              <div className="flex h-full min-h-[320px] items-end p-8 text-white lg:min-h-[480px]">
                <div>
                  <p className="text-xs uppercase tracking-[0.2em] text-white/70">
                    {STORE_NAME}
                  </p>
                  <p className="mt-2 text-2xl font-semibold">Urban essentials</p>
                </div>
              </div>
            )}
            {heroProduct ? (
              <div className="absolute bottom-4 left-4 right-4 rounded-xl bg-white/95 p-4 shadow-lg">
                <p className="text-xs uppercase tracking-wide text-slate-500">
                  {heroProduct.brand?.name ?? "Hàng mới"}
                </p>
                <p className="font-semibold">{heroProduct.name}</p>
                <Link
                  href={`/products/${heroProduct.slug}`}
                  className="mt-2 inline-flex text-sm font-semibold text-blue-600"
                >
                  Xem chi tiết
                </Link>
              </div>
            ) : null}
          </div>
        </div>
      </section>

      <section className="border-y border-slate-200 bg-white">
        <ul className={`${sfContainer} grid gap-6 py-6 sm:grid-cols-3`}>
          {[
            {
              icon: Truck,
              title: "Giao hàng toàn quốc",
              body: "Đóng gói cẩn thận, theo dõi đơn trên tài khoản.",
            },
            {
              icon: RefreshCw,
              title: "Đổi trả 30 ngày",
              body: "Đổi size hoặc hoàn nếu sản phẩm còn nguyên tem.",
            },
            {
              icon: ShieldCheck,
              title: "Hàng chính hãng",
              body: "Giá và tồn kho đồng bộ từ kho — không tự tính trên trình duyệt.",
            },
          ].map((item) => (
            <li key={item.title} className="flex gap-3">
              <item.icon className="mt-0.5 h-5 w-5 shrink-0 text-blue-600" />
              <div>
                <p className="text-sm font-semibold">{item.title}</p>
                <p className="mt-1 text-sm text-slate-500">{item.body}</p>
              </div>
            </li>
          ))}
        </ul>
      </section>

      <section className={`${sfContainer} py-14`}>
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
              Danh mục
            </p>
            <h2 className="mt-1 text-2xl font-semibold">Khám phá theo danh mục</h2>
          </div>
          <Link href="/products" className="text-sm font-semibold text-blue-600">
            Xem tất cả
          </Link>
        </div>
        {categoryError ? (
          <p className="mt-4 text-sm text-red-700" role="alert">
            Không tải được danh mục. Thử lại sau.
          </p>
        ) : categories.length === 0 ? (
          <p className="mt-4 text-sm text-slate-500">Chưa có danh mục.</p>
        ) : (
          <ul className="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            {categories.slice(0, 6).map((c, i) => (
              <li key={c.id}>
                <Link
                  href={`/products?category=${encodeURIComponent(c.slug)}`}
                  className="group flex aspect-[3/4] flex-col justify-end overflow-hidden rounded-xl p-4 text-white"
                  style={{
                    background:
                      i % 3 === 0
                        ? "#0f172a"
                        : i % 3 === 1
                          ? "#1e3a8a"
                          : "#334155",
                  }}
                >
                  <span className="text-sm font-semibold group-hover:underline">
                    {c.name}
                  </span>
                  <span className="mt-1 text-xs text-white/70">Mua ngay</span>
                </Link>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className={`${sfContainer} pb-14`}>
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
              Hàng mới
            </p>
            <h2 className="mt-1 text-2xl font-semibold">Sản phẩm nổi bật</h2>
          </div>
          <Link href="/products" className="text-sm font-semibold text-blue-600">
            Xem tất cả
          </Link>
        </div>
        {productError ? (
          <p className="mt-4 text-sm text-red-700" role="alert">
            Không tải được sản phẩm. Thử lại sau.
          </p>
        ) : products.length === 0 ? (
          <div className="mt-8">
            <EmptyState
              title="Chưa có sản phẩm"
              description="Hàng mới sẽ xuất hiện tại đây khi catalog sẵn sàng."
              actionHref="/products"
              actionLabel="Đến cửa hàng"
            />
          </div>
        ) : (
          <ul className="mt-8 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            {products.slice(0, 8).map((p) => (
              <li key={p.id}>
                <ProductCard product={p} />
              </li>
            ))}
          </ul>
        )}
      </section>

      {featured ? (
        <section className="bg-slate-950 text-white">
          <div className={`${sfContainer} grid items-center gap-8 py-14 lg:grid-cols-2`}>
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">
                Bộ sưu tập
              </p>
              <h2 className="mt-3 text-3xl font-semibold">
                The urban uniform — tinh gọn, bền bỉ
              </h2>
              <p className="mt-4 max-w-md text-sm leading-relaxed text-slate-300">
                {featured.name}. Khám phá chi tiết sản phẩm và phiên bản còn hàng.
              </p>
              <Link
                href={`/products/${featured.slug}`}
                className="mt-8 inline-flex h-12 items-center rounded-lg bg-white px-6 text-sm font-semibold text-slate-950 hover:bg-slate-100"
              >
                Xem ngay
              </Link>
            </div>
            <div className="relative aspect-[4/5] overflow-hidden rounded-xl bg-slate-800">
              {featuredSrc ? (
                <Image
                  src={featuredSrc}
                  alt={featured.primary_image?.alt ?? featured.name}
                  fill
                  sizes="(min-width: 1024px) 50vw, 100vw"
                  className="object-cover"
                />
              ) : null}
            </div>
          </div>
        </section>
      ) : null}

      {look.length > 0 ? (
        <section className={`${sfContainer} py-14`}>
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
            Gợi ý
          </p>
          <h2 className="mt-1 text-2xl font-semibold">Shop the look</h2>
          <ul className="mt-8 grid gap-4 md:grid-cols-2">
            {look.map((p) => (
              <li key={p.id}>
                <ProductCard product={p} />
              </li>
            ))}
          </ul>
        </section>
      ) : null}

      <section className="border-t border-slate-200 bg-slate-50">
        <div className={`${sfContainer} py-14 text-center`}>
          <p className="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
            Cam kết
          </p>
          <h2 className="mt-2 text-2xl font-semibold">Vì sao chọn {STORE_NAME}?</h2>
          <ul className="mt-8 grid gap-4 text-left sm:grid-cols-3">
            {[
              {
                title: "Giá minh bạch",
                body: "Hiển thị đúng giá variant từ API, kèm giá gốc khi có giảm.",
              },
              {
                title: "Giao nhanh",
                body: "Chọn phương thức vận chuyển phù hợp tại bước thanh toán.",
              },
              {
                title: "Hỗ trợ dễ dàng",
                body: "Quản lý địa chỉ, đơn hàng và hồ sơ ngay trong tài khoản.",
              },
            ].map((item) => (
              <li
                key={item.title}
                className="rounded-xl border border-slate-200 bg-white p-5"
              >
                <p className="font-semibold">{item.title}</p>
                <p className="mt-2 text-sm text-slate-500">{item.body}</p>
              </li>
            ))}
          </ul>
        </div>
      </section>
    </main>
  );
}
