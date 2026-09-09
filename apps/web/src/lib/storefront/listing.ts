export type ListingQuery = {
  q?: string;
  category?: string;
  brand?: string;
  sort?: string;
  page?: string;
};

export function normalizeSort(
  raw: string | undefined,
): "newest" | "price_asc" | "price_desc" {
  if (raw === "price_asc" || raw === "price_desc") return raw;
  return "newest";
}

export function listingHref(sp: {
  q?: string;
  category?: string;
  brand?: string;
  sort?: string;
  page?: string | number;
}): string {
  const p = new URLSearchParams();
  const q = sp.q?.trim();
  if (q) p.set("q", q);
  if (sp.category) p.set("category", sp.category);
  if (sp.brand) p.set("brand", sp.brand);
  const sort = normalizeSort(sp.sort);
  if (sort !== "newest") p.set("sort", sort);
  const page = Number(sp.page);
  if (Number.isFinite(page) && page > 1) p.set("page", String(page));
  const s = p.toString();
  return s ? `/products?${s}` : "/products";
}

export function listingCanonicalPath(sp: {
  category?: string;
  brand?: string;
}): string {
  return listingHref({ category: sp.category, brand: sp.brand });
}
