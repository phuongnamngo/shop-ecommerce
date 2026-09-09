import {
  StorefrontBrowserError,
  storefrontBrowserFetch,
} from "@/lib/api/storefront/browser";
import {
  addCustomerCartItem,
  fetchCustomerCart,
  fetchCustomerMeOrNull,
  mergeGuestCart,
  postCustomerCheckout,
  removeCustomerCartItem,
  updateCustomerCartItem,
} from "@/lib/api/storefront/customer";
import type {
  CheckoutBody,
  CheckoutCreated,
  StorefrontCart,
} from "@/lib/api/storefront/types";
import { emitCartChanged } from "@/lib/storefront/cart-events";

export const CART_TOKEN_KEY = "watch.cart_token";

export function getCartToken(): string | null {
  if (typeof window === "undefined") return null;
  const value = window.localStorage.getItem(CART_TOKEN_KEY);
  return value && value !== "" ? value : null;
}

export function setCartToken(token: string): void {
  if (typeof window === "undefined") return;
  window.localStorage.setItem(CART_TOKEN_KEY, token);
}

export function clearCartToken(): void {
  if (typeof window === "undefined") return;
  window.localStorage.removeItem(CART_TOKEN_KEY);
  emitCartChanged();
}

function isInvalidCartToken(error: unknown): boolean {
  return (
    error instanceof StorefrontBrowserError &&
    error.code === "CART_INVALID_TOKEN"
  );
}

function missingCartToken(): StorefrontBrowserError {
  return new StorefrontBrowserError(
    422,
    "A valid cart token is required.",
    "CART_INVALID_TOKEN",
  );
}

export async function ensureCartToken(): Promise<string> {
  const existing = getCartToken();
  if (existing) return existing;
  const { meta } = await storefrontBrowserFetch<StorefrontCart>("/api/v1/cart", {
    method: "POST",
  });
  const token =
    typeof meta?.cart_token === "string" ? meta.cart_token : null;
  if (!token) {
    throw new StorefrontBrowserError(500, "Cart token missing.");
  }
  setCartToken(token);
  return token;
}

export async function fetchCart(): Promise<StorefrontCart> {
  const token = getCartToken();
  if (!token) throw missingCartToken();
  try {
    const { data } = await storefrontBrowserFetch<StorefrontCart>(
      "/api/v1/cart",
      { cartToken: token },
    );
    return data;
  } catch (error) {
    if (isInvalidCartToken(error)) {
      clearCartToken();
    }
    throw error;
  }
}

export async function fetchActiveCart(): Promise<StorefrontCart> {
  const me = await fetchCustomerMeOrNull();
  if (me) {
    return fetchCustomerCart();
  }
  return fetchCart();
}

export async function addCartItem(
  product_variant_id: number,
  qty: number,
): Promise<StorefrontCart> {
  const me = await fetchCustomerMeOrNull();
  if (me) {
    const cart = await addCustomerCartItem(product_variant_id, qty);
    emitCartChanged();
    return cart;
  }

  const run = async (): Promise<StorefrontCart> => {
    const token = await ensureCartToken();
    const { data } = await storefrontBrowserFetch<StorefrontCart>(
      "/api/v1/cart/items",
      {
        method: "POST",
        cartToken: token,
        body: JSON.stringify({ product_variant_id, qty }),
      },
    );
    return data;
  };
  try {
    const cart = await run();
    emitCartChanged();
    return cart;
  } catch (error) {
    if (!isInvalidCartToken(error)) throw error;
    clearCartToken();
    const cart = await run();
    emitCartChanged();
    return cart;
  }
}

export async function updateCartItem(
  itemId: number,
  qty: number,
): Promise<StorefrontCart> {
  const me = await fetchCustomerMeOrNull();
  if (me) {
    const cart = await updateCustomerCartItem(itemId, qty);
    emitCartChanged();
    return cart;
  }

  const token = getCartToken();
  if (!token) throw missingCartToken();
  try {
    const { data } = await storefrontBrowserFetch<StorefrontCart>(
      `/api/v1/cart/items/${itemId}`,
      {
        method: "PATCH",
        cartToken: token,
        body: JSON.stringify({ qty }),
      },
    );
    emitCartChanged();
    return data;
  } catch (error) {
    if (isInvalidCartToken(error)) {
      clearCartToken();
    }
    throw error;
  }
}

export async function removeCartItem(itemId: number): Promise<void> {
  const me = await fetchCustomerMeOrNull();
  if (me) {
    await removeCustomerCartItem(itemId);
    emitCartChanged();
    return;
  }

  const token = getCartToken();
  if (!token) throw missingCartToken();
  try {
    await storefrontBrowserFetch<null>(`/api/v1/cart/items/${itemId}`, {
      method: "DELETE",
      cartToken: token,
    });
    emitCartChanged();
  } catch (error) {
    if (isInvalidCartToken(error)) {
      clearCartToken();
    }
    throw error;
  }
}

export async function postCheckout(
  body: CheckoutBody,
): Promise<CheckoutCreated> {
  const me = await fetchCustomerMeOrNull();
  if (me) {
    return postCustomerCheckout(body);
  }

  const token = getCartToken();
  if (!token) throw missingCartToken();
  try {
    const { data } = await storefrontBrowserFetch<CheckoutCreated>(
      "/api/v1/checkout",
      {
        method: "POST",
        cartToken: token,
        body: JSON.stringify(body),
      },
    );
    return data;
  } catch (error) {
    if (isInvalidCartToken(error)) {
      clearCartToken();
    }
    throw error;
  }
}

export async function mergeGuestCartIfPresent(): Promise<void> {
  const token = getCartToken();
  if (!token) return;
  try {
    await mergeGuestCart(token);
  } catch {
    // Login already succeeded; invalid or failed merge must not fail the session.
  }
  clearCartToken();
}
