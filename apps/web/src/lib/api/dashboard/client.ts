import { ApiError, apiFetch, getApiBaseUrl } from "@/lib/api/client";
import type { ApiErrorBody, ApiSuccess } from "@/lib/api/types";
import type { DashboardMetrics } from "@/lib/api/dashboard/types";

export function getDashboardMetrics(): Promise<ApiSuccess<DashboardMetrics>> {
  return apiFetch("/api/v1/admin/dashboard/metrics");
}

export async function downloadDashboardExport(asOf: string): Promise<void> {
  const res = await fetch(`${getApiBaseUrl()}/api/v1/admin/dashboard/export`, {
    method: "GET",
    credentials: "include",
  });

  if (!res.ok) {
    const body = (await res.json().catch(() => null)) as ApiErrorBody | null;
    throw new ApiError(res.status, body);
  }

  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = `dashboard-${asOf.slice(0, 10)}.xlsx`;
  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();
  URL.revokeObjectURL(url);
}
