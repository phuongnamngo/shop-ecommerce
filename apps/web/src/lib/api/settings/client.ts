import { apiFetch } from "@/lib/api/client";
import type { AdminSetting } from "@/lib/api/settings/types";
import type { ApiSuccess } from "@/lib/api/types";

export function listAdminSettings(): Promise<ApiSuccess<AdminSetting[]>> {
  return apiFetch("/api/v1/admin/settings");
}

export function patchAdminSetting(
  key: string,
  value: Record<string, unknown>,
): Promise<ApiSuccess<AdminSetting>> {
  return apiFetch(`/api/v1/admin/settings/${encodeURIComponent(key)}`, {
    method: "PATCH",
    json: { value },
  });
}
