import { storefrontBrowserFetch } from "@/lib/api/storefront/browser";

export type CatalogSuggestProduct = {
  id: number;
  name: string;
  slug: string;
  thumbnail_url: string | null;
  price: string | number | null;
};

export type CatalogSuggestGroup = {
  id: number;
  name: string;
  slug: string;
};

export type CatalogSuggest = {
  products: CatalogSuggestProduct[];
  categories: CatalogSuggestGroup[];
  brands: CatalogSuggestGroup[];
};

export async function suggestCatalog(q: string): Promise<CatalogSuggest> {
  const res = await storefrontBrowserFetch<CatalogSuggest>(
    `/api/v1/catalog/search/suggest?q=${encodeURIComponent(q)}`,
  );
  return {
    products: res.data.products ?? [],
    categories: res.data.categories ?? [],
    brands: res.data.brands ?? [],
  };
}
