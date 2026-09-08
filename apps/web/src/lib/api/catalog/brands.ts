import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { Brand, CatalogStatus, PageMeta } from "@/lib/api/catalog/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listBrands(params?: {
  page?: number;
  per_page?: number;
  status?: string;
}): Promise<ApiSuccess<Brand[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/catalog/brands${qs(params ?? {})}`) as Promise<
    ApiSuccess<Brand[]> & { meta: PageMeta }
  >;
}

export function getBrand(id: number): Promise<ApiSuccess<Brand>> {
  return apiFetch(`/api/v1/admin/catalog/brands/${id}`);
}

export function createBrand(body: {
  name: string;
  slug?: string | null;
  status?: CatalogStatus;
}): Promise<ApiSuccess<Brand>> {
  return apiFetch("/api/v1/admin/catalog/brands", { method: "POST", json: body });
}

export function updateBrand(
  id: number,
  body: { name?: string; slug?: string | null; status?: CatalogStatus },
): Promise<ApiSuccess<Brand>> {
  return apiFetch(`/api/v1/admin/catalog/brands/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteBrand(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/catalog/brands/${id}`, { method: "DELETE" });
}
