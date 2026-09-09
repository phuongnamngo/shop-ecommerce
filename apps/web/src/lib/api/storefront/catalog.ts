import { storefrontFetch } from "@/lib/api/storefront/client";
import type {
  PageMeta,
  PublicBrand,
  PublicCategoryNode,
  PublicProductDetail,
  PublicProductListItem,
} from "@/lib/api/storefront/types";

export function listPublicCategories(): Promise<PublicCategoryNode[]> {
  return storefrontFetch<PublicCategoryNode[]>(
    "/api/v1/catalog/categories",
  ).then((res) => res.data);
}

export function listPublicProducts(params: {
  q?: string;
  category_id?: number;
  brand_id?: number;
  sort?: "newest" | "price_asc" | "price_desc";
  page?: number;
  per_page?: number;
}): Promise<{ data: PublicProductListItem[]; meta: PageMeta }> {
  return storefrontFetch<PublicProductListItem[]>(
    "/api/v1/catalog/products",
    params,
  ).then((res) => ({
    data: res.data,
    meta: res.meta ?? {
      current_page: 1,
      per_page: params.per_page ?? 20,
      total: res.data.length,
      last_page: 1,
    },
  }));
}

export function getPublicProduct(slug: string): Promise<PublicProductDetail> {
  return storefrontFetch<PublicProductDetail>(
    `/api/v1/catalog/products/${encodeURIComponent(slug)}`,
  ).then((res) => res.data);
}

export async function listAllPublicBrands(): Promise<PublicBrand[]> {
  const brands: PublicBrand[] = [];
  let page = 1;
  let lastPage = 1;
  do {
    const res = await storefrontFetch<PublicBrand[]>("/api/v1/catalog/brands", {
      page,
      per_page: 100,
    });
    brands.push(...res.data);
    lastPage = res.meta?.last_page ?? 1;
    page += 1;
  } while (page <= lastPage);
  return brands;
}
