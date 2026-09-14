import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { PageMeta } from "@/lib/api/orders/types";
import type {
  AdminRefund,
  PaymentTotals,
  PaymentTransactionRow,
} from "@/lib/api/payments/types";

function qs(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value === undefined || value === "") continue;
    search.set(key, String(value));
  }
  const s = search.toString();
  return s ? `?${s}` : "";
}

export function listPaymentTransactions(params?: {
  page?: number;
  per_page?: number;
  provider?: string;
  status?: string;
  refund_status?: string;
  q?: string;
  from?: string;
  to?: string;
}): Promise<
  ApiSuccess<PaymentTransactionRow[]> & { meta: PageMeta & PaymentTotals }
> {
  return apiFetch(
    `/api/v1/admin/payments/transactions${qs(params ?? {})}`,
  ) as Promise<
    ApiSuccess<PaymentTransactionRow[]> & { meta: PageMeta & PaymentTotals }
  >;
}

export function getPaymentTransaction(
  id: number,
): Promise<ApiSuccess<PaymentTransactionRow>> {
  return apiFetch(`/api/v1/admin/payments/transactions/${id}`);
}

export function createOrderRefund(
  orderId: number,
  body: { reason: string },
): Promise<ApiSuccess<AdminRefund>> {
  return apiFetch(`/api/v1/admin/orders/${orderId}/refunds`, {
    method: "POST",
    json: body,
  });
}

export function approveRefund(id: number): Promise<ApiSuccess<AdminRefund>> {
  return apiFetch(`/api/v1/admin/refunds/${id}/approve`, { method: "POST" });
}

export function rejectRefund(id: number): Promise<ApiSuccess<AdminRefund>> {
  return apiFetch(`/api/v1/admin/refunds/${id}/reject`, { method: "POST" });
}

export function retryRefund(id: number): Promise<ApiSuccess<AdminRefund>> {
  return apiFetch(`/api/v1/admin/refunds/${id}/retry`, { method: "POST" });
}
