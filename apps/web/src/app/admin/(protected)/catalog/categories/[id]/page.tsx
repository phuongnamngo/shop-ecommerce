import { CategoryEditPage } from "@/components/admin/catalog/categories-pages";

export default async function AdminCatalogCategoryEditPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <CategoryEditPage categoryId={Number(id)} />;
}
