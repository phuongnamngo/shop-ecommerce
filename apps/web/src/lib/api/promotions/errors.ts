import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function promotionErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "PROMOTION_DISCOUNT_IN_USE":
        return "Không xóa được: discount vẫn còn coupon gắn kèm.";
      case "PROMOTION_NOT_FOUND":
        return "Không tìm thấy bản ghi khuyến mãi.";
      case "FLASH_SALE_NOT_FOUND":
        return "Không tìm thấy flash sale.";
      case "FLASH_SALE_OVERLAP":
        return "Variant này đã nằm trong flash sale scheduled/active trùng khung giờ.";
      case "FLASH_SALE_QTY_EXCEEDED":
        return "Đã hết suất flash sale cho sản phẩm này.";
      case "FLASH_SALE_INVALID":
        return "Flash sale không hợp lệ.";
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
