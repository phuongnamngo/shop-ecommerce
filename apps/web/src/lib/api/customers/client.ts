import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { AdminCustomer, PageMeta } from "@/lib/api/customers/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listCustomers(params?: {
  page?: number;
  per_page?: number;
  status?: string;
  q?: string;
}): Promise<ApiSuccess<AdminCustomer[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/customers${qs(params ?? {})}`) as Promise<
    ApiSuccess<AdminCustomer[]> & { meta: PageMeta }
  >;
}

export function getCustomer(id: number): Promise<ApiSuccess<AdminCustomer>> {
  return apiFetch(`/api/v1/admin/customers/${id}`);
}
