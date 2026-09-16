import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { AdminActivity, PageMeta } from "@/lib/api/activity/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listAdminActivity(params?: {
  page?: number;
  per_page?: number;
  log_name?: string;
}): Promise<ApiSuccess<AdminActivity[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/activity${qs(params ?? {})}`) as Promise<
    ApiSuccess<AdminActivity[]> & { meta: PageMeta }
  >;
}
