export type CmsPageStatus = "draft" | "published";

export type CmsPage = {
  id: number;
  slug: string;
  title: string;
  body: string;
  status: CmsPageStatus;
  published_at: string | null;
};

export type CmsBannerPlacement = "promo_bar" | "homepage_hero";

export type CmsBannerStatus = "active" | "inactive";

export type CmsBanner = {
  id: number;
  placement: CmsBannerPlacement;
  title: string | null;
  image_url: string | null;
  link_url: string | null;
  starts_at: string | null;
  ends_at: string | null;
  sort: number;
  status: CmsBannerStatus;
};
