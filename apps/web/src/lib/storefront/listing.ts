export type ListingQuery = {
  q?: string;
  category?: string;
  brand?: string;
  sort?: string;
  page?: string;
  price_bucket?: string;
  attrs?: string | string[];
};

export type ListingHrefInput = {
  q?: string;
  category?: string;
  brand?: string;
  sort?: string;
  page?: string | number;
  price_bucket?: string;
  attrs?: string[];
};

export function normalizeSort(
  raw: string | undefined,
): "newest" | "price_asc" | "price_desc" {
  if (raw === "price_asc" || raw === "price_desc") return raw;
  return "newest";
}

export function normalizeAttrs(
  raw: string | string[] | undefined,
): string[] {
  if (!raw) return [];
  return (Array.isArray(raw) ? raw : [raw]).map((v) => v.trim()).filter(Boolean);
}

export function listingHref(sp: ListingHrefInput): string {
  const p = new URLSearchParams();
  const q = sp.q?.trim();
  if (q) p.set("q", q);
  if (sp.category) p.set("category", sp.category);
  if (sp.brand) p.set("brand", sp.brand);
  const sort = normalizeSort(sp.sort);
  if (sort !== "newest") p.set("sort", sort);
  if (sp.price_bucket) p.set("price_bucket", sp.price_bucket);
  for (const attr of sp.attrs ?? []) {
    p.append("attrs", attr);
  }
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

export function listingShouldNoindex(sp: ListingQuery): boolean {
  return Boolean(
    sp.q?.trim() || sp.price_bucket || normalizeAttrs(sp.attrs).length > 0,
  );
}

export function toggleAttr(attrs: string[], token: string): string[] {
  return attrs.includes(token)
    ? attrs.filter((item) => item !== token)
    : [...attrs, token];
}
