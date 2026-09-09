import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type {
  AdminOrder,
  OrderShipment,
  PageMeta,
} from "@/lib/api/orders/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listOrders(params?: {
  page?: number;
  per_page?: number;
  status?: string;
  customer_id?: number;
}): Promise<ApiSuccess<AdminOrder[]> & { meta: PageMeta }> {
  return apiFetch(`/api/v1/admin/orders${qs(params ?? {})}`) as Promise<
    ApiSuccess<AdminOrder[]> & { meta: PageMeta }
  >;
}

export function getOrder(id: number): Promise<ApiSuccess<AdminOrder>> {
  return apiFetch(`/api/v1/admin/orders/${id}`);
}

export function updateOrderStatus(
  id: number,
  body: { status: string; note?: string | null },
): Promise<ApiSuccess<AdminOrder>> {
  return apiFetch(`/api/v1/admin/orders/${id}/status`, {
    method: "PATCH",
    json: body,
  });
}

export function createOrderShipment(
  id: number,
  body: { tracking_number: string; carrier_code?: string | null },
): Promise<
  ApiSuccess<
    Pick<OrderShipment, "id" | "tracking_number" | "carrier_code" | "status">
  >
> {
  return apiFetch(`/api/v1/admin/orders/${id}/shipments`, {
    method: "POST",
    json: body,
  });
}
