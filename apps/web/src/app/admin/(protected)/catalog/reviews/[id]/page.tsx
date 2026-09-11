import { ReviewDetailPage } from "@/components/admin/reviews/reviews-pages";

export default async function AdminCatalogReviewDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <ReviewDetailPage id={Number(id)} />;
}
