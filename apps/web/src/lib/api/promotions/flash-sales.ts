import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  FlashSale,
  FlashSaleStatus,
  PageMeta,
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

export type FlashSaleItemWrite = {
  product_variant_id: number;
  sale_price: number;
  qty_cap?: number | null;
};

export type FlashSaleWriteBody = {
  name: string;
  code?: string | null;
  starts_at: string;
  ends_at: string;
  status?: FlashSaleStatus;
  items?: FlashSaleItemWrite[];
};

export function listFlashSales(params?: {
  page?: number;
  per_page?: number;
  q?: string;
  status?: string;
}): Promise<ApiSuccess<FlashSale[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/promotions/flash-sales${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<FlashSale[]> & { meta: PageMeta }>;
}

export function getFlashSale(id: number): Promise<ApiSuccess<FlashSale>> {
  return apiFetch(`/api/v1/admin/promotions/flash-sales/${id}`);
}

export function createFlashSale(
  body: FlashSaleWriteBody,
): Promise<ApiSuccess<FlashSale>> {
  return apiFetch("/api/v1/admin/promotions/flash-sales", {
    method: "POST",
    json: body,
  });
}

export function updateFlashSale(
  id: number,
  body: Partial<FlashSaleWriteBody>,
): Promise<ApiSuccess<FlashSale>> {
  return apiFetch(`/api/v1/admin/promotions/flash-sales/${id}`, {
    method: "PATCH",
    json: body,
  });
}

export async function deleteFlashSale(id: number): Promise<void> {
  await apiFetch(`/api/v1/admin/promotions/flash-sales/${id}`, {
    method: "DELETE",
  });
}
