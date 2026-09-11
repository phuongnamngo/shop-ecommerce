import { CouponEditPage } from "@/components/admin/promotions/coupons-pages";

export default async function AdminPromotionsCouponEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <CouponEditPage couponId={Number(id)} />;
}
