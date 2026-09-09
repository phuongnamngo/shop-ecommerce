import { StorefrontFooter } from "@/components/storefront/storefront-footer";
import { StorefrontHeader } from "@/components/storefront/storefront-header";
import { listPublicCategories } from "@/lib/api/storefront/catalog";

export default async function StorefrontLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  let categories: Array<{ name: string; slug: string }> = [];
  try {
    const tree = await listPublicCategories();
    categories = tree.map((c) => ({ name: c.name, slug: c.slug }));
  } catch {
    categories = [];
  }

  return (
    <div className="flex min-h-full flex-col">
      <StorefrontHeader categories={categories} />
      <div className="flex-1">{children}</div>
      <StorefrontFooter />
    </div>
  );
}
