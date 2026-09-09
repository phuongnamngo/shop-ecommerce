import { ApiError } from "@/lib/api/client";
import { messageForAuthError } from "@/lib/api/admin-auth";

export function orderErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    switch (error.code) {
      case "ORDER_INVALID_TRANSITION":
        return "Không chuyển được trạng thái đơn (transition không hợp lệ).";
      case "SHIPMENT_INVALID_STATUS":
        return "Không ship được: đơn phải ở fulfilling và có reservation active.";
      case "SHIPMENT_TRACKING_REQUIRED":
        return "Tracking number là bắt buộc.";
      case "SHIPMENT_ALREADY_EXISTS":
        return "Đơn đã có shipment.";
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
