import { OrderDetailPage } from "@/components/admin/orders/order-detail-page";

export default async function AdminOrderDetailRoute({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <OrderDetailPage orderId={Number(id)} />;
}
