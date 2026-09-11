export type PromotionStatus = "active" | "inactive";
export type DiscountType = "fixed" | "percentage";
export type FlashSaleStatus = "scheduled" | "active" | "ended" | "cancelled";

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

export type FlashSaleItem = {
  id: number;
  product_variant_id: number;
  sale_price: string;
  qty_cap: number | null;
  qty_sold: number;
  qty_remaining: number | null;
};

export type FlashSale = {
  id: number;
  code: string;
  name: string;
  starts_at: string;
  ends_at: string;
  status: FlashSaleStatus;
  items: FlashSaleItem[];
  created_at: string;
  updated_at: string;
};
