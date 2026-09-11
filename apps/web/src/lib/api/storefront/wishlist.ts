import { storefrontSessionFetch } from "@/lib/api/storefront/session";

export type WishlistProduct = {
  id: number;
  name: string;
  slug: string;
};

export type WishlistItem = {
  id: number;
  product_variant_id: number;
  product: WishlistProduct | null;
  sku: string | null;
  price: string | null;
  thumbnail: { url: string; thumbnail_url: string } | null;
};

export type Wishlist = {
  id: number;
  name: string;
  items: WishlistItem[];
};

export async function fetchWishlist(): Promise<Wishlist> {
  const { data } = await storefrontSessionFetch<Wishlist>(
    "/api/v1/customer/wishlist",
  );
  return data;
}

export async function addWishlistItem(
  product_variant_id: number,
): Promise<Wishlist> {
  const { data } = await storefrontSessionFetch<Wishlist>(
    "/api/v1/customer/wishlist/items",
    { method: "POST", json: { product_variant_id } },
  );
  return data;
}

export async function removeWishlistItem(id: number): Promise<void> {
  await storefrontSessionFetch<null>(`/api/v1/customer/wishlist/items/${id}`, {
    method: "DELETE",
  });
}
