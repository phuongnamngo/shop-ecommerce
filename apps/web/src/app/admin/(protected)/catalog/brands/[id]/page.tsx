import { BrandEditPage } from "@/components/admin/catalog/brands-pages";

export default async function AdminCatalogBrandEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <BrandEditPage brandId={Number(id)} />;
}
