import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  CatalogImage,
  CatalogStatus,
  PageMeta,
  Product,
  ProductVariant,
  UploadImageResult,
} from "@/lib/api/catalog/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export type CreateVariantInput = {
  sku: string;
  price: number;
  is_default: boolean;
  barcode?: string | null;
  compare_at_price?: number | null;
  status?: CatalogStatus;
};

export function listProducts(params?: {
  page?: number;
  per_page?: number;
  q?: string;
  status?: string;
  brand_id?: number;
  category_id?: number;
}): Promise<ApiSuccess<Product[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/catalog/products${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<Product[]> & { meta: PageMeta }>;
}

export function getProduct(id: number): Promise<ApiSuccess<Product>> {
  return apiFetch(`/api/v1/admin/catalog/products/${id}`);
}

export function createProduct(body: {
  name: string;
  slug?: string | null;
  brand_id?: number | null;
  status?: CatalogStatus;
  published_at?: string | null;
  description?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
  category_ids?: number[];
  variants: CreateVariantInput[];
}): Promise<ApiSuccess<Product>> {
  return apiFetch("/api/v1/admin/catalog/products", {
    method: "POST",
    json: body,
  });
}

export function updateProduct(
  id: number,
  body: Partial<{
    name: string;
    slug: string | null;
    brand_id: number | null;
    status: CatalogStatus;
    published_at: string | null;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
    category_ids: number[];
  }>,
): Promise<ApiSuccess<Product>> {
  return apiFetch(`/api/v1/admin/catalog/products/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteProduct(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/catalog/products/${id}`, { method: "DELETE" });
}

export function createVariant(
  productId: number,
  body: CreateVariantInput,
): Promise<ApiSuccess<ProductVariant>> {
  return apiFetch(`/api/v1/admin/catalog/products/${productId}/variants`, {
    method: "POST",
    json: body,
  });
}

export function updateVariant(
  productId: number,
  variantId: number,
  body: Partial<CreateVariantInput>,
): Promise<ApiSuccess<ProductVariant>> {
  return apiFetch(
    `/api/v1/admin/catalog/products/${productId}/variants/${variantId}`,
    { method: "PATCH", json: body },
  );
}

export async function deleteVariant(
  productId: number,
  variantId: number,
): Promise<void> {
  await apiFetch(
    `/api/v1/admin/catalog/products/${productId}/variants/${variantId}`,
    { method: "DELETE" },
  );
}

export function uploadCatalogImage(
  file: File,
): Promise<ApiSuccess<UploadImageResult>> {
  const fd = new FormData();
  fd.append("file", file);
  return apiFetch("/api/v1/admin/catalog/uploads/images", {
    method: "POST",
    body: fd,
  });
}

export function attachProductImage(
  productId: number,
  body: {
    path: string;
    alt?: string | null;
    position?: number;
    is_primary?: boolean;
  },
): Promise<ApiSuccess<CatalogImage>> {
  return apiFetch(`/api/v1/admin/catalog/products/${productId}/images`, {
    method: "POST",
    json: body,
  });
}

export async function deleteProductImage(
  productId: number,
  imageId: number,
): Promise<void> {
  await apiFetch(
    `/api/v1/admin/catalog/products/${productId}/images/${imageId}`,
    { method: "DELETE" },
  );
}
