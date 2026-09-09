import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function dashboardErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.status === 403) {
      return "Bạn không có quyền xem dashboard.";
    }
    const field = error.errors.find((e) => e.field);
    if (field?.message) {
      return field.message;
    }
  }
  return messageForAuthError(error);
}
