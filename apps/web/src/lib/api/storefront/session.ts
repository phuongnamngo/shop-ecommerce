import {
  StorefrontBrowserError,
  getStorefrontBrowserApiBase,
} from "@/lib/api/storefront/browser";

type Envelope<T> = {
  data: T;
  meta?: Record<string, unknown>;
  errors?: Array<{ code?: string; message?: string; field?: string | null }>;
};

function readXsrfToken(): string | null {
  if (typeof document === "undefined") {
    return null;
  }
  const match = document.cookie
    .split("; ")
    .find((row) => row.startsWith("XSRF-TOKEN="));
  if (!match) {
    return null;
  }
  return decodeURIComponent(match.slice("XSRF-TOKEN=".length));
}

async function ensureCsrfCookie(): Promise<void> {
  const base = getStorefrontBrowserApiBase();
  const res = await fetch(`${base}/sanctum/csrf-cookie`, {
    method: "GET",
    credentials: "include",
    cache: "no-store",
    headers: { Accept: "application/json" },
  });
  if (!res.ok) {
    throw new StorefrontBrowserError(
      res.status,
      "Failed to obtain CSRF cookie",
    );
  }
}

type SessionFetchInit = RequestInit & {
  json?: unknown;
  retryOn419?: boolean;
};

export async function storefrontSessionFetch<T>(
  path: string,
  init: SessionFetchInit = {},
): Promise<{ data: T; meta?: Record<string, unknown> }> {
  const base = getStorefrontBrowserApiBase();
  const method = (init.method ?? "GET").toUpperCase();
  const mutating = !["GET", "HEAD", "OPTIONS"].includes(method);

  if (mutating) {
    await ensureCsrfCookie();
  }

  const { json, retryOn419, headers: initHeaders, ...fetchInit } = init;
  const headers = new Headers(initHeaders);
  headers.set("Accept", "application/json");
  if (json !== undefined) {
    headers.set("Content-Type", "application/json");
  }

  const xsrf = readXsrfToken();
  if (mutating && xsrf) {
    headers.set("X-XSRF-TOKEN", xsrf);
  }

  const url = path.startsWith("http") ? path : `${base}${path}`;
  const res = await fetch(url, {
    ...fetchInit,
    method,
    headers,
    credentials: "include",
    cache: "no-store",
    body: json !== undefined ? JSON.stringify(json) : fetchInit.body,
  });

  if (res.status === 419 && retryOn419 !== false) {
    await ensureCsrfCookie();
    return storefrontSessionFetch<T>(path, { ...init, retryOn419: false });
  }

  const body = (await res.json().catch(() => null)) as Envelope<T> | null;
  if (!res.ok) {
    const primary = body?.errors?.[0];
    throw new StorefrontBrowserError(
      res.status,
      primary?.message ?? `Request failed (${res.status})`,
      primary?.code,
      primary?.field,
    );
  }

  if (!body || !("data" in body)) {
    throw new StorefrontBrowserError(res.status, "Invalid storefront response");
  }

  return { data: body.data, meta: body.meta };
}
