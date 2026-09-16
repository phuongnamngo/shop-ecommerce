import { cache } from "react";

import { storefrontFetch } from "@/lib/api/storefront/client";
import { STORE_NAME } from "@/lib/storefront/ui";

export type PublicSettings = {
  site: { name: string };
  currency: { code: string };
};

export const getPublicSettings = cache(async (): Promise<PublicSettings> => {
  try {
    const res = await storefrontFetch<PublicSettings>("/api/v1/settings");
    const name = res.data.site?.name?.trim() || STORE_NAME;
    const code = res.data.currency?.code?.trim() || "VND";
    return { site: { name }, currency: { code } };
  } catch {
    return { site: { name: STORE_NAME }, currency: { code: "VND" } };
  }
});
