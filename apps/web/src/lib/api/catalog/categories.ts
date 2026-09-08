import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  CatalogStatus,
  Category,
  PageMeta,
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

export function listCategories(params?: {
  page?: number;
  per_page?: number;
  q?: string;
  status?: string;
}): Promise<ApiSuccess<Category[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/catalog/categories${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<Category[]> & { meta: PageMeta }>;
}

export function getCategory(id: number): Promise<ApiSuccess<Category>> {
  return apiFetch(`/api/v1/admin/catalog/categories/${id}`);
}

export function createCategory(body: {
  name: string;
  slug?: string | null;
  parent_id?: number | null;
  position?: number;
  status?: CatalogStatus;
  description?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
}): Promise<ApiSuccess<Category>> {
  return apiFetch("/api/v1/admin/catalog/categories", {
    method: "POST",
    json: body,
  });
}

export function updateCategory(
  id: number,
  body: Partial<{
    name: string;
    slug: string | null;
    parent_id: number | null;
    position: number;
    status: CatalogStatus;
    description: string | null;
    meta_title: string | null;
    meta_description: string | null;
  }>,
): Promise<ApiSuccess<Category>> {
  return apiFetch(`/api/v1/admin/catalog/categories/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteCategory(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/catalog/categories/${id}`, {
    method: "DELETE",
  });
}
