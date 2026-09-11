import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function reviewErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "REVIEW_NOT_FOUND":
        return "Không tìm thấy đánh giá.";
      case "REVIEW_NOT_ELIGIBLE":
        return "Khách chưa đủ điều kiện đánh giá sản phẩm này.";
      case "REVIEW_ALREADY_EXISTS":
        return "Khách đã có đánh giá cho sản phẩm này.";
      case "REVIEW_NOT_EDITABLE":
        return "Đánh giá này không còn sửa được.";
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
