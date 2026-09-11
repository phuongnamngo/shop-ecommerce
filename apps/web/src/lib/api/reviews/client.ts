import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  AdminReview,
  PageMeta,
  ReviewStatus,
} from "@/lib/api/reviews/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listAdminReviews(params?: {
  page?: number;
  per_page?: number;
  q?: string;
  status?: string;
  product_id?: number;
}): Promise<ApiSuccess<AdminReview[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/reviews${qs(params ?? {})}`) as Promise<
    ApiSuccess<AdminReview[]> & { meta: PageMeta }
  >;
}

export function getAdminReview(id: number): Promise<ApiSuccess<AdminReview>> {
  return apiFetch(`/api/v1/admin/reviews/${id}`);
}

export function patchAdminReview(
  id: number,
  body: { status: Extract<ReviewStatus, "approved" | "rejected"> },
): Promise<ApiSuccess<AdminReview>> {
  return apiFetch(`/api/v1/admin/reviews/${id}`, {
    method: "PATCH",
    json: body,
  });
}
