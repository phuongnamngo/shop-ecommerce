import { storefrontBrowserFetch } from "@/lib/api/storefront/browser";
import type {
  GeoNode,
  GuestOrder,
  ShippingMethod,
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

export async function lookupGuestOrder(token: string): Promise<GuestOrder> {
  const { data } = await storefrontBrowserFetch<GuestOrder>(
    `/api/v1/orders/lookup?token=${encodeURIComponent(token)}`,
  );
  return data;
}

export { storefrontErrorMessage } from "@/lib/api/storefront/browser";
