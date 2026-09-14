import { StorefrontBrowserError, storefrontBrowserFetch } from "@/lib/api/storefront/browser";
import { getCartToken } from "@/lib/api/storefront/cart";
import { fetchCustomerMeOrNull } from "@/lib/api/storefront/customer";
import { storefrontSessionFetch } from "@/lib/api/storefront/session";
import type {
  GeoNode,
  GuestOrder,
  ShippingMethod,
  ShippingQuote,
} from "@/lib/api/storefront/types";

export async function listShippingMethods(): Promise<ShippingMethod[]> {
  const { data } = await storefrontBrowserFetch<ShippingMethod[]>(
    "/api/v1/shipping/methods",
  );
  return data;
}

export async function listProvinces(): Promise<GeoNode[]> {
  const { data } = await storefrontBrowserFetch<GeoNode[]>(
    "/api/v1/geo/provinces",
  );
  return data;
}

export async function listDistricts(provinceCode: string): Promise<GeoNode[]> {
  const { data } = await storefrontBrowserFetch<GeoNode[]>(
    `/api/v1/geo/provinces/${encodeURIComponent(provinceCode)}/districts`,
  );
  return data;
}

export async function listWards(districtCode: string): Promise<GeoNode[]> {
  const { data } = await storefrontBrowserFetch<GeoNode[]>(
    `/api/v1/geo/districts/${encodeURIComponent(districtCode)}/wards`,
  );
  return data;
}

export async function postShippingQuotes(body: {
  province_code: string;
  district_code: string;
  ward_code: string;
}): Promise<ShippingQuote[]> {
  const me = await fetchCustomerMeOrNull();
  if (me) {
    const { data } = await storefrontSessionFetch<ShippingQuote[]>(
      "/api/v1/shipping/quotes",
      { method: "POST", json: body },
    );
    return data;
  }

  const token = getCartToken();
  if (!token) {
    throw new StorefrontBrowserError(
      422,
      "A valid cart token is required.",
      "CART_INVALID_TOKEN",
    );
  }

  const { data } = await storefrontBrowserFetch<ShippingQuote[]>(
    "/api/v1/shipping/quotes",
    {
      method: "POST",
      cartToken: token,
      body: JSON.stringify(body),
    },
  );
  return data;
}

export async function lookupGuestOrder(token: string): Promise<GuestOrder> {
  const { data } = await storefrontBrowserFetch<GuestOrder>(
    `/api/v1/orders/lookup?token=${encodeURIComponent(token)}`,
  );
  return data;
}

export { storefrontErrorMessage } from "@/lib/api/storefront/browser";
