import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function cmsErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "CMS_PAGE_NOT_FOUND":
        return "Không tìm thấy trang CMS.";
      case "CMS_BANNER_NOT_FOUND":
        return "Không tìm thấy banner CMS.";
      case "CMS_SLUG_TAKEN":
        return "Slug đã được dùng.";
      default:
        break;
    }
    const field = error.errors.find((e) => e.field);
    if (field?.message) {
      return field.message;
    }
    if (error.message) {
      return error.message;
    }
  }
  return messageForAuthError(error);
}
