const LABELS: Record<string, string> = {
  pending: "Chờ xác nhận",
  paid: "Đang xử lý",
  fulfilling: "Đang xử lý",
  shipped: "Đang giao",
  completed: "Hoàn thành",
  cancelled: "Đã hủy",
  failed: "Thất bại",
};

export function orderStatusLabel(status: string): string {
  return LABELS[status] ?? status;
}

export function orderStatusClass(status: string): string {
  if (status === "completed" || status === "paid") {
    return "bg-emerald-50 text-emerald-700";
  }
  if (status === "cancelled" || status === "failed") {
    return "bg-red-50 text-red-700";
  }
  if (status === "shipped") {
    return "bg-blue-50 text-blue-700";
  }
  return "bg-amber-50 text-amber-800";
}
