import { Be_Vietnam_Pro } from "next/font/google";

import { StorefrontChrome } from "@/components/storefront/storefront-chrome";
import { listPublicCategories } from "@/lib/api/storefront/catalog";

const beVietnam = Be_Vietnam_Pro({
  subsets: ["latin", "vietnamese"],
  weight: ["400", "500", "600", "700"],
  variable: "--font-storefront-sans",
});

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
    <div className={`${beVietnam.variable} storefront flex min-h-full flex-col`}>
      <StorefrontChrome categories={categories}>{children}</StorefrontChrome>
    </div>
  );
}
