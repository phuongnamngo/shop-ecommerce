import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function promotionErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "PROMOTION_DISCOUNT_IN_USE":
        return "Không xóa được: discount vẫn còn coupon gắn kèm.";
      case "PROMOTION_NOT_FOUND":
        return "Không tìm thấy bản ghi khuyến mãi.";
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
