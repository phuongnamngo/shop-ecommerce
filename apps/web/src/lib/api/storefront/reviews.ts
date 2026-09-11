import { storefrontSessionFetch } from "@/lib/api/storefront/session";
import type { PageMeta } from "@/lib/api/storefront/types";

export type ReviewEligibilityReason =
  | "ok"
  | "not_purchased"
  | "pending"
  | "rejected"
  | "approved";

export type ReviewEligibility = {
  eligible: boolean;
  reason: ReviewEligibilityReason;
  existing_review: { id: number; status: string } | null;
};

export type CustomerReview = {
  id: number;
  product_id: number;
  product_variant_id: number | null;
  rating: number;
  body: string | null;
  status: string;
  images?: Array<{ id: number; url: string; thumbnail_url: string }>;
  created_at: string | null;
};

export type PublicProductReview = {
  id: number;
  author_name: string;
  rating: number;
  body: string | null;
  product_variant_id: number | null;
  variant_label: string | null;
  images?: Array<{ url: string; thumbnail_url: string }>;
  created_at: string | null;
};

export type PublicReviewsMeta = PageMeta & {
  rating_avg: number | null;
  rating_count: number;
  rating_histogram: Record<"1" | "2" | "3" | "4" | "5", number>;
};

export async function listProductReviews(
  slug: string,
  page = 1,
): Promise<{ data: PublicProductReview[]; meta: PublicReviewsMeta }> {
  const { data, meta } = await storefrontSessionFetch<PublicProductReview[]>(
    `/api/v1/catalog/products/${encodeURIComponent(slug)}/reviews?page=${page}&per_page=10`,
  );
  return {
    data,
    meta: {
      current_page: Number(meta?.current_page ?? 1),
      last_page: Number(meta?.last_page ?? 1),
      per_page: Number(meta?.per_page ?? 10),
      total: Number(meta?.total ?? 0),
      rating_avg: (meta?.rating_avg as number | null | undefined) ?? null,
      rating_count: Number(meta?.rating_count ?? 0),
      rating_histogram: (meta?.rating_histogram as PublicReviewsMeta["rating_histogram"]) ?? {
        "5": 0,
        "4": 0,
        "3": 0,
        "2": 0,
        "1": 0,
      },
    },
  };
}

export async function fetchReviewEligibility(
  productId: number,
): Promise<ReviewEligibility> {
  const { data } = await storefrontSessionFetch<ReviewEligibility>(
    `/api/v1/customer/products/${productId}/review-eligibility`,
  );
  return data;
}

export async function createReview(
  formData: FormData,
): Promise<CustomerReview> {
  const { data } = await storefrontSessionFetch<CustomerReview>(
    "/api/v1/customer/reviews",
    { method: "POST", body: formData },
  );
  return data;
}

export async function updateReview(
  id: number,
  formData: FormData,
): Promise<CustomerReview> {
  const { data } = await storefrontSessionFetch<CustomerReview>(
    `/api/v1/customer/reviews/${id}`,
    { method: "PATCH", body: formData },
  );
  return data;
}
