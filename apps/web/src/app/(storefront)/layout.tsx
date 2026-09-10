import { Be_Vietnam_Pro } from "next/font/google";
import type { Metadata } from "next";

import { StorefrontChrome } from "@/components/storefront/storefront-chrome";
import { listPublicCategories } from "@/lib/api/storefront/catalog";
import { leafCategories } from "@/lib/api/storefront/resolve";

const beVietnam = Be_Vietnam_Pro({
  subsets: ["latin", "vietnamese"],
  weight: ["400", "500", "600", "700"],
  variable: "--font-storefront-sans",
});

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: {
    default: "Watch",
    template: "%s",
  },
  description:
    "Thời trang nam tối giản. Áo thun, polo, jean và phụ kiện — giá và tồn kho từ hệ thống.",
};

export default async function StorefrontLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  let categories: Array<{ name: string; slug: string }> = [];
  try {
    const tree = await listPublicCategories();
    categories = leafCategories(tree).map((c) => ({
      name: c.name,
      slug: c.slug,
    }));
  } catch {
    categories = [];
  }

  return (
    <div className={`${beVietnam.variable} storefront flex min-h-full flex-col`}>
      <StorefrontChrome categories={categories}>{children}</StorefrontChrome>
    </div>
  );
}
