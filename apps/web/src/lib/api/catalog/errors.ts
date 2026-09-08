import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function catalogErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "CATALOG_VARIANT_INVARIANT":
        return "Variants không hợp lệ: cần đúng một variant mặc định.";
      case "CATALOG_RESOURCE_IN_USE":
        return "Không xóa được: resource đang được sử dụng.";
      case "CATALOG_NOT_FOUND":
        return "Không tìm thấy bản ghi catalog.";
      default:
        break;
    }
    const field = error.errors.find((e) => e.field);
    if (field?.message) {
      return field.message;
    }
  }
  return messageForAuthError(error);
}
