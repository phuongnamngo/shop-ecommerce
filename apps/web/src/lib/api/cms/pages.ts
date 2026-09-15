import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { PageMeta } from "@/lib/api/catalog/types";
import type { CmsPage, CmsPageStatus } from "@/lib/api/cms/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listCmsPages(params?: {
  page?: number;
  per_page?: number;
  status?: string;
}): Promise<ApiSuccess<CmsPage[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/cms/pages${qs(params ?? {})}`) as Promise<
    ApiSuccess<CmsPage[]> & { meta: PageMeta }
  >;
}

export function getCmsPage(id: number): Promise<ApiSuccess<CmsPage>> {
  return apiFetch(`/api/v1/admin/cms/pages/${id}`);
}

export function createCmsPage(body: {
  title: string;
  body: string;
  slug?: string | null;
  status?: CmsPageStatus;
}): Promise<ApiSuccess<CmsPage>> {
  return apiFetch("/api/v1/admin/cms/pages", { method: "POST", json: body });
}

export function updateCmsPage(
  id: number,
  body: {
    title?: string;
    body?: string;
    slug?: string | null;
    status?: CmsPageStatus;
  },
): Promise<ApiSuccess<CmsPage>> {
  return apiFetch(`/api/v1/admin/cms/pages/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteCmsPage(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/cms/pages/${id}`, { method: "DELETE" });
}
