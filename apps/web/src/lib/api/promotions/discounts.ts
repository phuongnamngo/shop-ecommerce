import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  Discount,
  DiscountType,
  PageMeta,
  PromotionStatus,
} from "@/lib/api/promotions/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export type DiscountWriteBody = {
  name: string;
  type: DiscountType;
  value: number;
  status?: PromotionStatus;
  starts_at?: string | null;
  ends_at?: string | null;
  rule?: { conditions: { min_subtotal?: number } } | null;
};

export function listDiscounts(params?: {
  page?: number;
  per_page?: number;
  q?: string;
  status?: string;
  type?: string;
}): Promise<ApiSuccess<Discount[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/promotions/discounts${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<Discount[]> & { meta: PageMeta }>;
}

export function getDiscount(id: number): Promise<ApiSuccess<Discount>> {
  return apiFetch(`/api/v1/admin/promotions/discounts/${id}`);
}

export function createDiscount(
  body: DiscountWriteBody,
): Promise<ApiSuccess<Discount>> {
  return apiFetch("/api/v1/admin/promotions/discounts", {
    method: "POST",
    json: body,
  });
}

export function updateDiscount(
  id: number,
  body: Partial<DiscountWriteBody>,
): Promise<ApiSuccess<Discount>> {
  return apiFetch(`/api/v1/admin/promotions/discounts/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteDiscount(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/promotions/discounts/${id}`, {
    method: "DELETE",
  });
}
