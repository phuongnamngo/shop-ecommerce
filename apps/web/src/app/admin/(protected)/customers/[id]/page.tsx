import { CustomerDetailPage } from "@/components/admin/customers/customer-detail-page";

export default async function AdminCustomerDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const customerId = Number.parseInt(id, 10);

  return (
    <CustomerDetailPage
      customerId={Number.isFinite(customerId) ? customerId : 0}
    />
  );
}
