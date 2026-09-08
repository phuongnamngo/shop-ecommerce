import type { ApiErrorBody, ApiErrorItem, ApiSuccess } from "@/lib/api/types";

export class ApiError extends Error {
  status: number;
  code?: string;
  errors: ApiErrorItem[];

  constructor(status: number, body: ApiErrorBody | null, fallbackMessage?: string) {
    const errors = body?.errors ?? [];
    const primary = errors[0];
    super(primary?.message ?? fallbackMessage ?? `Request failed (${status})`);
    this.name = "ApiError";
    this.status = status;
    this.code = primary?.code;
    this.errors = errors;
  }
}

export function getApiBaseUrl(): string {
  const base = process.env.NEXT_PUBLIC_API_URL?.trim();
  if (!base) {
    throw new Error("NEXT_PUBLIC_API_URL is not set");
  }
  return base.replace(/\/$/, "");
}

export function readXsrfToken(): string | null {
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

export async function ensureCsrfCookie(): Promise<void> {
  const base = getApiBaseUrl();
  const res = await fetch(`${base}/sanctum/csrf-cookie`, {
    method: "GET",
    credentials: "include",
    headers: { Accept: "application/json" },
  });
  if (!res.ok) {
    throw new ApiError(res.status, null, "Failed to obtain CSRF cookie");
  }
}

type ApiFetchInit = RequestInit & {
  json?: unknown;
  retryOn419?: boolean;
};

export async function apiFetch<T>(
  path: string,
  init: ApiFetchInit = {},
): Promise<ApiSuccess<T>> {
  const base = getApiBaseUrl();
  const method = (init.method ?? "GET").toUpperCase();
  const mutating = !["GET", "HEAD", "OPTIONS"].includes(method);

  if (mutating) {
    await ensureCsrfCookie();
  }

  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.json !== undefined) {
    headers.set("Content-Type", "application/json");
  }

  const xsrf = readXsrfToken();
  if (mutating && xsrf) {
    headers.set("X-XSRF-TOKEN", xsrf);
  }

  const { json, retryOn419, ...rest } = init;
  const res = await fetch(`${base}${path}`, {
    ...rest,
    method,
    headers,
    credentials: "include",
    body: json !== undefined ? JSON.stringify(json) : rest.body,
  });

  if (res.status === 419 && retryOn419 !== false) {
    await ensureCsrfCookie();
    return apiFetch<T>(path, { ...init, retryOn419: false });
  }

  const body = (await res.json().catch(() => null)) as
    | ApiSuccess<T>
    | ApiErrorBody
    | null;

  if (!res.ok) {
    throw new ApiError(res.status, body as ApiErrorBody | null);
  }

  return body as ApiSuccess<T>;
}
