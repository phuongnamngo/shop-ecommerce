import { FlashSaleEditPage } from "@/components/admin/promotions/flash-sales-pages";

export default async function AdminPromotionsFlashSaleEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <FlashSaleEditPage saleId={Number(id)} />;
}
