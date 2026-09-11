import { DiscountEditPage } from "@/components/admin/promotions/discounts-pages";

export default async function AdminPromotionsDiscountEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <DiscountEditPage discountId={Number(id)} />;
}
