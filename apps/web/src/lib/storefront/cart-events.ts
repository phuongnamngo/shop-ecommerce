export const CART_CHANGED_EVENT = "watch-cart-changed";

export function emitCartChanged(): void {
  if (typeof window === "undefined") return;
  window.dispatchEvent(new CustomEvent(CART_CHANGED_EVENT));
}
