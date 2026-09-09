import { apiFetch } from "@/lib/api/client";
import type { ApiSuccess } from "@/lib/api/types";
import type { DashboardMetrics } from "@/lib/api/dashboard/types";

export function getDashboardMetrics(): Promise<ApiSuccess<DashboardMetrics>> {
  return apiFetch("/api/v1/admin/dashboard/metrics");
}
