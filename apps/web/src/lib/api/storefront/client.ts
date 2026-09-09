import type { PageMeta } from "@/lib/api/storefront/types";

type Envelope<T> = {
  data: T;
  meta?: PageMeta;
  errors?: Array<{ code?: string; message?: string }>;
};

export class StorefrontApiError extends Error {
  status: number;
  code?: string;

  constructor(status: number, message: string, code?: string) {
    super(message);
    this.name = "StorefrontApiError";
    this.status = status;
    this.code = code;
  }
}

export function getStorefrontApiBase(): string {
  const base = (process.env.API_URL ?? process.env.NEXT_PUBLIC_API_URL)?.trim();
  if (!base) {
    throw new Error("NEXT_PUBLIC_API_URL is not set");
  }
  return base.replace(/\/$/, "");
}

export async function storefrontFetch<T>(
  path: string,
  searchParams?: Record<string, string | number | undefined>,
): Promise<{ data: T; meta?: PageMeta }> {
  const base = getStorefrontApiBase();
  const url = new URL(path.startsWith("http") ? path : `${base}${path}`);
  if (searchParams) {
    for (const [key, value] of Object.entries(searchParams)) {
      if (value === undefined || value === "") continue;
      url.searchParams.set(key, String(value));
    }
  }

  const res = await fetch(url.toString(), {
    method: "GET",
    headers: { Accept: "application/json" },
    next: { revalidate: 60 },
  });

  const body = (await res.json().catch(() => null)) as Envelope<T> | null;
  if (!res.ok) {
    const primary = body?.errors?.[0];
    throw new StorefrontApiError(
      res.status,
      primary?.message ?? `Request failed (${res.status})`,
      primary?.code,
    );
  }

  if (!body || !("data" in body)) {
    throw new StorefrontApiError(res.status, "Invalid catalog response");
  }

  return { data: body.data, meta: body.meta };
}

export function absoluteMediaUrl(
  url: string | null | undefined,
): string | null {
  if (!url) return null;
  if (/^https?:\/\//i.test(url)) return url;
  const origin = getStorefrontApiBase();
  if (url.startsWith("/")) return `${origin}${url}`;
  return `${origin}/${url}`;
}
