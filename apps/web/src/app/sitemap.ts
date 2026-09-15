import type { MetadataRoute } from "next";

import { listPublicProducts } from "@/lib/api/storefront/catalog";
import { listPublicCmsPages } from "@/lib/api/storefront/cms";

function siteOrigin(): string {
  return (
    process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"
  ).replace(/\/$/, "");
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const origin = siteOrigin();
  const entries: MetadataRoute.Sitemap = [
    { url: `${origin}/` },
    { url: `${origin}/products` },
  ];

  try {
    const pages = await listPublicCmsPages();
    for (const page of pages) {
      entries.push({ url: `${origin}/pages/${page.slug}` });
    }
  } catch {
    /* keep static entries */
  }

  try {
    let page = 1;
    let lastPage = 1;
    do {
      const res = await listPublicProducts({
        sort: "newest",
        page,
        per_page: 100,
      });
      for (const p of res.data) {
        entries.push({ url: `${origin}/products/${p.slug}` });
      }
      lastPage = res.meta.last_page;
      page += 1;
    } while (page <= lastPage);
  } catch {
    return entries;
  }

  return entries;
}
