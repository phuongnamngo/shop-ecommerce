export class StorefrontBrowserError extends Error {
  status: number;
  code?: string;

  constructor(status: number, message: string, code?: string) {
    super(message);
    this.name = "StorefrontBrowserError";
    this.status = status;
    this.code = code;
  }
}

type Envelope<T> = {
  data: T;
  meta?: Record<string, unknown>;
  errors?: Array<{ code?: string; message?: string; field?: string | null }>;
};

export function getStorefrontBrowserApiBase(): string {
  const base = process.env.NEXT_PUBLIC_API_URL?.trim();
  if (!base) {
    throw new Error("NEXT_PUBLIC_API_URL is not set");
  }
  return base.replace(/\/$/, "");
}

export function storefrontMediaUrl(
  url: string | null | undefined,
): string | null {
  if (!url) return null;
  if (/^https?:\/\//i.test(url)) return url;
  const origin = getStorefrontBrowserApiBase();
  if (url.startsWith("/")) return `${origin}${url}`;
  return `${origin}/${url}`;
}

export async function storefrontBrowserFetch<T>(
  path: string,
  init: RequestInit & { cartToken?: string | null } = {},
): Promise<{ data: T; meta?: Record<string, unknown> }> {
  const { cartToken, headers: initHeaders, ...rest } = init;
  const headers = new Headers(initHeaders);
  headers.set("Accept", "application/json");
  headers.set("Content-Type", "application/json");
  if (cartToken) {
    headers.set("X-Cart-Token", cartToken);
  }

  const base = getStorefrontBrowserApiBase();
  const url = path.startsWith("http") ? path : `${base}${path}`;
  const res = await fetch(url, {
    ...rest,
    headers,
    credentials: "omit",
    cache: "no-store",
  });

  const body = (await res.json().catch(() => null)) as Envelope<T> | null;
  if (!res.ok) {
    const primary = body?.errors?.[0];
    throw new StorefrontBrowserError(
      res.status,
      primary?.message ?? `Request failed (${res.status})`,
      primary?.code,
    );
  }

  if (!body || !("data" in body)) {
    throw new StorefrontBrowserError(res.status, "Invalid storefront response");
  }

  return { data: body.data, meta: body.meta };
}

export function storefrontErrorMessage(error: unknown): string {
  if (error instanceof StorefrontBrowserError) {
    switch (error.code) {
      case "CART_INVALID_TOKEN":
        return "Giỏ hàng không còn hiệu lực. Thêm lại sản phẩm.";
      case "COUPON_INVALID":
        return "Mã giảm giá không hợp lệ.";
      case "CHECKOUT_INVALID_CART":
        return "Không thể thanh toán giỏ hàng này.";
      case "INVENTORY_INSUFFICIENT_STOCK":
        return "Không đủ hàng trong kho.";
      case "ORDER_LOOKUP_INVALID":
        return "Không tìm thấy đơn hàng.";
      case "PAYMENT_METHOD_INVALID":
        return "Phương thức thanh toán không khả dụng.";
      default:
        return error.message || `Lỗi ${error.status}`;
    }
  }
  return "Không kết nối được máy chủ. Thử lại sau.";
}
