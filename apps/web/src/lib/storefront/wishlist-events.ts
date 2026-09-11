export const WISHLIST_CHANGED_EVENT = "watch-wishlist-changed";

export function emitWishlistChanged(): void {
  if (typeof window === "undefined") return;
  window.dispatchEvent(new CustomEvent(WISHLIST_CHANGED_EVENT));
}
