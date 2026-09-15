import { storefrontFetch } from "@/lib/api/storefront/client";

export type PublicCmsPageListItem = {
  slug: string;
  title: string;
};

export type PublicCmsPage = {
  slug: string;
  title: string;
  body_html: string;
  published_at: string | null;
};

export type PublicCmsBanner = {
  id: number;
  placement: string;
  title: string | null;
  image_url: string | null;
  link_url: string | null;
  sort: number;
};

export type PublicCmsBanners = {
  promo_bar: PublicCmsBanner[];
  homepage_hero: PublicCmsBanner[];
};

export function listPublicCmsPages(): Promise<PublicCmsPageListItem[]> {
  return storefrontFetch<PublicCmsPageListItem[]>("/api/v1/cms/pages").then(
    (res) => res.data,
  );
}

export function getPublicCmsPage(slug: string): Promise<PublicCmsPage> {
  return storefrontFetch<PublicCmsPage>(
    `/api/v1/cms/pages/${encodeURIComponent(slug)}`,
  ).then((res) => res.data);
}

export function listPublicCmsBanners(): Promise<PublicCmsBanners> {
  return storefrontFetch<PublicCmsBanners>("/api/v1/cms/banners").then(
    (res) => res.data,
  );
}
