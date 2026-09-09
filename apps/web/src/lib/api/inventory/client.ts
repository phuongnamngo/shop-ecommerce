import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  PageMeta,
  StockItem,
  StockMovementType,
  Warehouse,
} from "@/lib/api/inventory/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listWarehouses(params?: {
  page?: number;
  per_page?: number;
}): Promise<ApiSuccess<Warehouse[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/inventory/warehouses${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<Warehouse[]> & { meta: PageMeta }>;
}

export function listStockItems(params?: {
  page?: number;
  per_page?: number;
  warehouse_id?: number;
  product_variant_id?: number;
}): Promise<ApiSuccess<StockItem[]> & { meta: PageMeta }> {
  return apiFetch(
    `/api/v1/admin/inventory/stock-items${qs(params ?? {})}`,
  ) as Promise<ApiSuccess<StockItem[]> & { meta: PageMeta }>;
}

export function createStockMovement(body: {
  warehouse_id: number;
  product_variant_id: number;
  type: StockMovementType;
  qty: number;
  note?: string | null;
}): Promise<ApiSuccess<StockItem>> {
  return apiFetch(`/api/v1/admin/inventory/movements`, {
    method: "POST",
    json: body,
  });
}
