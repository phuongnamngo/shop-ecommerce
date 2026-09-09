export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type PublicImage = {
  url: string | null;
  thumbnail_url: string | null;
  alt: string | null;
};

export type PublicBrand = {
  id: number;
  name: string;
  slug: string;
};

export type PublicCategoryNode = {
  id: number;
  name: string;
  slug: string;
  position: number;
  description: string | null;
  meta_title: string | null;
  meta_description: string | null;
  children: PublicCategoryNode[];
};

export type PublicProductListItem = {
  id: number;
  name: string;
  slug: string;
  brand: PublicBrand | null;
  primary_image: PublicImage | null;
  default_variant: {
    id: number;
    sku: string;
    price: string | number;
    compare_at_price: string | number | null;
  } | null;
};

export type PublicVariant = {
  id: number;
  sku: string;
  price: string | number;
  compare_at_price: string | number | null;
  is_default: boolean;
  attributes: Array<{
    id: number | null;
    name: string | null;
    slug: string | null;
    option: { id: number; label: string };
  }>;
  images: Array<
    PublicImage & {
      id?: number;
      path?: string;
      position?: number;
      is_primary?: boolean;
    }
  >;
};

export type PublicProductDetail = PublicProductListItem & {
  description: string | null;
  meta_title: string | null;
  meta_description: string | null;
  categories: Array<
    Pick<
      PublicCategoryNode,
      | "id"
      | "name"
      | "slug"
      | "position"
      | "description"
      | "meta_title"
      | "meta_description"
    >
  >;
  images: PublicVariant["images"];
  variants: PublicVariant[];
};
