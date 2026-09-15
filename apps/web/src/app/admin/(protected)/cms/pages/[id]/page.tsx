import { CmsPageEditPage } from "@/components/admin/cms/pages-pages";

export default async function AdminCmsPageEditRoute({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  return <CmsPageEditPage pageId={Number(id)} />;
}
