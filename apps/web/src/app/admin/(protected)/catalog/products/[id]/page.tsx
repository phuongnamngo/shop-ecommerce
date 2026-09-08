import { ProductEditPage } from "@/components/admin/catalog/products-pages";

export default async function AdminCatalogProductEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <ProductEditPage productId={Number(id)} />;
}
