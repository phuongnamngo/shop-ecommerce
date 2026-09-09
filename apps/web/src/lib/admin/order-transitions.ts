/**
 * Mirror App\Services\Order\OrderService::TRANSITIONS.
 * Do not PATCH "shipped" — use POST .../shipments instead.
 */
export const ORDER_TRANSITIONS: Record<string, string[]> = {
  pending: ["paid", "cancelled"],
  paid: ["fulfilling", "cancelled"],
  fulfilling: ["cancelled"],
  shipped: ["completed"],
  completed: [],
  cancelled: [],
};

export function nextOrderStatuses(status: string): string[] {
  return ORDER_TRANSITIONS[status] ?? [];
}
