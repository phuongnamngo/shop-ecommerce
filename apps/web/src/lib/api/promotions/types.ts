export type PromotionStatus = "active" | "inactive";
export type DiscountType = "fixed" | "percentage";

export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type DiscountRule = {
  id: number;
  conditions: { min_subtotal?: number };
};

export type Discount = {
  id: number;
  code: string;
  name: string;
  type: DiscountType;
  value: string;
  starts_at: string | null;
  ends_at: string | null;
  status: PromotionStatus;
  rule: DiscountRule | null;
  created_at: string;
  updated_at: string;
};

export type CouponDiscountSummary = {
  id: number;
  code: string;
  name: string;
  type: DiscountType;
  value: string;
  status: PromotionStatus;
};

export type Coupon = {
  id: number;
  code: string;
  discount_id: number;
  discount: CouponDiscountSummary | null;
  max_uses: number | null;
  max_uses_per_customer: number | null;
  used_count: number;
  starts_at: string | null;
  ends_at: string | null;
  status: PromotionStatus;
  created_at: string;
  updated_at: string;
};
