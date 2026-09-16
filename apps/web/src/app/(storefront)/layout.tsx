import { Be_Vietnam_Pro } from "next/font/google";
import type { Metadata } from "next";

import { StorefrontChrome } from "@/components/storefront/storefront-chrome";
import { listPublicCategories } from "@/lib/api/storefront/catalog";
import {
  listPublicCmsBanners,
  listPublicCmsPages,
  type PublicCmsBanner,
  type PublicCmsPageListItem,
} from "@/lib/api/storefront/cms";
import { leafCategories } from "@/lib/api/storefront/resolve";
import { getPublicSettings } from "@/lib/api/storefront/settings";

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
  let promo: PublicCmsBanner | null = null;
  let pages: PublicCmsPageListItem[] = [];
  const settings = await getPublicSettings();
  const storeName = settings.site.name;

  try {
    const tree = await listPublicCategories();
    categories = leafCategories(tree).map((c) => ({
      name: c.name,
      slug: c.slug,
    }));
  } catch {
    categories = [];
  }

  try {
    const banners = await listPublicCmsBanners();
    promo = banners.promo_bar[0] ?? null;
  } catch {
    promo = null;
  }

  try {
    pages = await listPublicCmsPages();
  } catch {
    pages = [];
  }

  return (
    <div className={`${beVietnam.variable} storefront flex min-h-full flex-col`}>
      <StorefrontChrome
        categories={categories}
        promo={promo}
        pages={pages}
        storeName={storeName}
      >
        {children}
      </StorefrontChrome>
    </div>
  );
}
