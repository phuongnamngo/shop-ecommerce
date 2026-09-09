import {
  StorefrontBrowserError,
} from "@/lib/api/storefront/browser";
import { storefrontSessionFetch } from "@/lib/api/storefront/session";
import type {
  CheckoutBody,
  CheckoutCreated,
  CustomerAddress,
  CustomerAddressBody,
  CustomerOrder,
  CustomerProfile,
  PageMeta,
  StorefrontCart,
} from "@/lib/api/storefront/types";

export async function fetchCustomerMe(): Promise<CustomerProfile> {
  const { data } = await storefrontSessionFetch<CustomerProfile>(
    "/api/v1/customer/me",
  );
  return data;
}

export async function fetchCustomerMeOrNull(): Promise<CustomerProfile | null> {
  try {
    return await fetchCustomerMe();
  } catch (error) {
    if (error instanceof StorefrontBrowserError && error.status === 401) {
      return null;
    }
    throw error;
  }
}

export async function patchCustomerMe(body: {
  name: string;
  phone?: string | null;
}): Promise<CustomerProfile> {
  const { data } = await storefrontSessionFetch<CustomerProfile>(
    "/api/v1/customer/me",
    { method: "PATCH", json: body },
  );
  return data;
}

export async function registerCustomer(body: {
  name: string;
  email: string;
  phone?: string;
  password: string;
  password_confirmation: string;
}): Promise<CustomerProfile> {
  const { data } = await storefrontSessionFetch<CustomerProfile>(
    "/api/v1/customer/auth/register",
    { method: "POST", json: body },
  );
  return data;
}

export async function loginCustomer(body: {
  email: string;
  password: string;
}): Promise<CustomerProfile> {
  const { data } = await storefrontSessionFetch<CustomerProfile>(
    "/api/v1/customer/auth/login",
    { method: "POST", json: body },
  );
  return data;
}

export async function logoutCustomer(): Promise<void> {
  try {
    await storefrontSessionFetch<null>("/api/v1/customer/auth/logout", {
      method: "POST",
      json: {},
    });
  } catch (error) {
    if (error instanceof StorefrontBrowserError && error.status === 401) {
      return;
    }
    throw error;
  }
}

export async function forgotCustomerPassword(email: string): Promise<void> {
  await storefrontSessionFetch("/api/v1/customer/auth/forgot-password", {
    method: "POST",
    json: { email },
  });
}

export async function resetCustomerPassword(body: {
  email: string;
  token: string;
  password: string;
  password_confirmation: string;
}): Promise<void> {
  await storefrontSessionFetch("/api/v1/customer/auth/reset-password", {
    method: "POST",
    json: body,
  });
}

export async function listCustomerAddresses(): Promise<CustomerAddress[]> {
  const { data } = await storefrontSessionFetch<CustomerAddress[]>(
    "/api/v1/customer/addresses",
  );
  return data;
}

export async function createCustomerAddress(
  body: CustomerAddressBody,
): Promise<CustomerAddress> {
  const { data } = await storefrontSessionFetch<CustomerAddress>(
    "/api/v1/customer/addresses",
    { method: "POST", json: body },
  );
  return data;
}

export async function updateCustomerAddress(
  id: number,
  body: CustomerAddressBody,
): Promise<CustomerAddress> {
  const { data } = await storefrontSessionFetch<CustomerAddress>(
    `/api/v1/customer/addresses/${id}`,
    { method: "PATCH", json: body },
  );
  return data;
}

export async function deleteCustomerAddress(id: number): Promise<void> {
  await storefrontSessionFetch<null>(`/api/v1/customer/addresses/${id}`, {
    method: "DELETE",
  });
}

export async function listCustomerOrders(
  page = 1,
): Promise<{ data: CustomerOrder[]; meta: PageMeta }> {
  const { data, meta } = await storefrontSessionFetch<CustomerOrder[]>(
    `/api/v1/customer/orders?page=${page}&per_page=15`,
  );
  return {
    data,
    meta: {
      current_page: Number(meta?.current_page ?? 1),
      last_page: Number(meta?.last_page ?? 1),
      per_page: Number(meta?.per_page ?? 15),
      total: Number(meta?.total ?? 0),
    },
  };
}

export async function fetchCustomerOrder(id: number): Promise<CustomerOrder> {
  const { data } = await storefrontSessionFetch<CustomerOrder>(
    `/api/v1/customer/orders/${id}`,
  );
  return data;
}

export async function mergeGuestCart(
  guest_token: string,
): Promise<StorefrontCart> {
  const { data } = await storefrontSessionFetch<StorefrontCart>(
    "/api/v1/customer/cart/merge",
    { method: "POST", json: { guest_token } },
  );
  return data;
}

export async function fetchCustomerCart(): Promise<StorefrontCart> {
  const { data } = await storefrontSessionFetch<StorefrontCart>(
    "/api/v1/customer/cart",
  );
  return data;
}

export async function addCustomerCartItem(
  product_variant_id: number,
  qty: number,
): Promise<StorefrontCart> {
  const { data } = await storefrontSessionFetch<StorefrontCart>(
    "/api/v1/customer/cart/items",
    { method: "POST", json: { product_variant_id, qty } },
  );
  return data;
}

export async function updateCustomerCartItem(
  itemId: number,
  qty: number,
): Promise<StorefrontCart> {
  const { data } = await storefrontSessionFetch<StorefrontCart>(
    `/api/v1/customer/cart/items/${itemId}`,
    { method: "PATCH", json: { qty } },
  );
  return data;
}

export async function removeCustomerCartItem(itemId: number): Promise<void> {
  await storefrontSessionFetch<null>(
    `/api/v1/customer/cart/items/${itemId}`,
    { method: "DELETE" },
  );
}

export async function postCustomerCheckout(
  body: CheckoutBody,
): Promise<CheckoutCreated> {
  const { data } = await storefrontSessionFetch<CheckoutCreated>(
    "/api/v1/checkout",
    { method: "POST", json: body },
  );
  return data;
}
