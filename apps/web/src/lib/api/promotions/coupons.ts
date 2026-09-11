import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { Coupon, PageMeta, PromotionStatus } from "@/lib/api/promotions/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export type CouponWriteBody = {
  code: string;
  discount_id: number;
  max_uses?: number | null;
  max_uses_per_customer?: number | null;
  starts_at?: string | null;
  ends_at?: string | null;
  status?: PromotionStatus;
};

export function listCoupons(params?: {
  page?: number;
  per_page?: number;
  q?: string;
  status?: string;
  discount_id?: number;
}): Promise<ApiSuccess<Coupon[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/promotions/coupons${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<Coupon[]> & { meta: PageMeta }>;
}

export function getCoupon(id: number): Promise<ApiSuccess<Coupon>> {
  return apiFetch(`/api/v1/admin/promotions/coupons/${id}`);
}

export function createCoupon(
  body: CouponWriteBody,
): Promise<ApiSuccess<Coupon>> {
  return apiFetch("/api/v1/admin/promotions/coupons", {
    method: "POST",
    json: body,
  });
}

export function updateCoupon(
  id: number,
  body: Partial<CouponWriteBody>,
): Promise<ApiSuccess<Coupon>> {
  return apiFetch(`/api/v1/admin/promotions/coupons/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteCoupon(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/promotions/coupons/${id}`, {
    method: "DELETE",
  });
}
