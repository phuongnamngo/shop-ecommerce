import { storefrontSessionFetch } from "@/lib/api/storefront/session";
import type { CustomerNotification, PageMeta } from "@/lib/api/storefront/types";

export async function listCustomerNotifications(
  page = 1,
  perPage = 15,
): Promise<{ data: CustomerNotification[]; meta: PageMeta & { unread_count: number } }> {
  const { data, meta } = await storefrontSessionFetch<CustomerNotification[]>(
    `/api/v1/customer/notifications?page=${page}&per_page=${perPage}`,
  );
  return {
    data,
    meta: {
      current_page: Number(meta?.current_page ?? 1),
      last_page: Number(meta?.last_page ?? 1),
      per_page: Number(meta?.per_page ?? perPage),
      total: Number(meta?.total ?? 0),
      unread_count: Number(meta?.unread_count ?? 0),
    },
  };
}

export async function markCustomerNotificationRead(
  id: string,
): Promise<CustomerNotification> {
  const { data } = await storefrontSessionFetch<CustomerNotification>(
    `/api/v1/customer/notifications/${id}`,
    { method: "PATCH", json: { read: true } },
  );
  return data;
}

export async function markAllCustomerNotificationsRead(): Promise<void> {
  await storefrontSessionFetch<null>("/api/v1/customer/notifications/read-all", {
    method: "POST",
    json: {},
  });
}
