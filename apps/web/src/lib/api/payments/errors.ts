import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function paymentErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "REFUND_NOT_ELIGIBLE":
        return "Đơn không đủ điều kiện hoàn tiền.";
      case "REFUND_ALREADY_OPEN":
        return "Đã có yêu cầu hoàn tiền đang mở.";
      case "REFUND_ALREADY_SUCCEEDED":
        return "Giao dịch này đã được hoàn.";
      case "REFUND_INVALID_STATUS":
        return "Trạng thái hoàn tiền không cho phép thao tác này.";
      case "REFUND_GATEWAY_FAILED":
        return "Cổng thanh toán hoàn thất bại. Có thể thử lại.";
      case "ORDER_REFUND_IN_PROGRESS":
        return "Không thể fulfillment khi đang có hoàn tiền mở.";
      case "PAYMENT_NOT_FOUND":
        return "Không tìm thấy giao dịch.";
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
