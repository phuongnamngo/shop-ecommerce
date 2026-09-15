import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { PageMeta } from "@/lib/api/catalog/types";
import type {
  CmsBanner,
  CmsBannerPlacement,
  CmsBannerStatus,
} from "@/lib/api/cms/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listCmsBanners(params?: {
  page?: number;
  per_page?: number;
  placement?: string;
}): Promise<ApiSuccess<CmsBanner[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/cms/banners${qs(params ?? {})}`) as Promise<
    ApiSuccess<CmsBanner[]> & { meta: PageMeta }
  >;
}

export function getCmsBanner(id: number): Promise<ApiSuccess<CmsBanner>> {
  return apiFetch(`/api/v1/admin/cms/banners/${id}`);
}

export function createCmsBanner(body: {
  placement: CmsBannerPlacement;
  title?: string | null;
  image_url?: string | null;
  link_url?: string | null;
  starts_at?: string | null;
  ends_at?: string | null;
  sort?: number;
  status?: CmsBannerStatus;
}): Promise<ApiSuccess<CmsBanner>> {
  return apiFetch("/api/v1/admin/cms/banners", { method: "POST", json: body });
}

export function updateCmsBanner(
  id: number,
  body: {
    placement?: CmsBannerPlacement;
    title?: string | null;
    image_url?: string | null;
    link_url?: string | null;
    starts_at?: string | null;
    ends_at?: string | null;
    sort?: number;
    status?: CmsBannerStatus;
  },
): Promise<ApiSuccess<CmsBanner>> {
  return apiFetch(`/api/v1/admin/cms/banners/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteCmsBanner(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/cms/banners/${id}`, { method: "DELETE" });
}
