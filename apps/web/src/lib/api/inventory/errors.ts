import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function inventoryErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.code === "INVENTORY_INSUFFICIENT_STOCK") {
      return "Không đủ tồn khả dụng (on hand − reserved).";
    }
    if (error.status === 403) {
      return "Bạn không có quyền thao tác tồn kho.";
    }
    if (error.status === 404) {
      return "Không tìm thấy dòng tồn kho.";
    }
    const field = error.errors.find((e) => e.field);
    if (field?.message) {
      return field.message;
    }
  }
  return messageForAuthError(error);
}
