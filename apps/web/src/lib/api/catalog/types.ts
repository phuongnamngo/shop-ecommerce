export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type CatalogStatus = "draft" | "active" | "inactive";

export type Brand = {
  id: number;
  code: string;
  name: string;
  slug: string;
  status: CatalogStatus;
};

export type Category = {
  id: number;
  code: string;
  name: string;
  slug: string;
  status: CatalogStatus;
  parent_id: number | null;
  position?: number;
  description?: string | null;
  meta_title?: string | null;
  meta_description?: string | null;
};

export type ProductVariant = {
  id: number;
  sku: string;
  barcode: string | null;
  price: string | number;
  compare_at_price: string | number | null;
  is_default: boolean;
  status: CatalogStatus;
};

export type CatalogImage = {
  id: number;
  path: string;
  alt: string | null;
  position: number;
  is_primary: boolean;
  url: string | null;
  thumbnail_url: string | null;
};

export type Product = {
  id: number;
  code: string;
  name: string;
  slug: string;
  status: CatalogStatus;
  published_at: string | null;
  description: string | null;
  meta_title: string | null;
  meta_description: string | null;
  brand: Pick<Brand, "id" | "code" | "name" | "slug" | "status"> | null;
  categories: Array<
    Pick<Category, "id" | "code" | "name" | "slug" | "status" | "parent_id">
  >;
  variants: ProductVariant[];
  images: CatalogImage[];
};

export type UploadImageResult = {
  path: string;
  url?: string;
  thumbnail_url?: string;
};
