export type ReviewStatus = "pending" | "approved" | "rejected";

export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type AdminReviewImage = {
  id: number;
  url: string;
  thumbnail_url: string;
};

export type AdminReview = {
  id: number;
  status: ReviewStatus;
  rating: number;
  body: string | null;
  product_variant_id: number | null;
  product: { id: number; name: string; slug: string } | null;
  customer: { id: number; name: string; email: string } | null;
  images?: AdminReviewImage[];
  created_at: string | null;
};
