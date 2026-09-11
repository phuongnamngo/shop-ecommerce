export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  facets?: CatalogFacets;
};

export type CatalogFacetOption = {
  token: string;
  label: string;
  count: number;
};

export type CatalogFacets = {
  brands: Array<{ id: number; name: string; slug: string; count: number }>;
  categories: Array<{ id: number; name: string; slug: string; count: number }>;
  price_buckets: Array<{ token: string; label: string; count: number }>;
  attributes: Array<{
    slug: string;
    name: string;
    options: CatalogFacetOption[];
  }>;
};

export type PublicImage = {
  url: string | null;
  thumbnail_url: string | null;
  alt: string | null;
};

export type PublicBrand = {
  id: number;
  name: string;
  slug: string;
};

export type PublicCategoryNode = {
  id: number;
  name: string;
  slug: string;
  position: number;
  description: string | null;
  meta_title: string | null;
  meta_description: string | null;
  children: PublicCategoryNode[];
};

export type PublicProductListItem = {
  id: number;
  name: string;
  slug: string;
  brand: PublicBrand | null;
  primary_image: PublicImage | null;
  default_variant: {
    id: number;
    sku: string;
    price: string | number;
    compare_at_price: string | number | null;
  } | null;
};

export type PublicVariant = {
  id: number;
  sku: string;
  price: string | number;
  compare_at_price: string | number | null;
  is_default: boolean;
  attributes: Array<{
    id: number | null;
    name: string | null;
    slug: string | null;
    option: { id: number; label: string };
  }>;
  images: Array<
    PublicImage & {
      id?: number;
      path?: string;
      position?: number;
      is_primary?: boolean;
    }
  >;
};

export type PublicProductDetail = PublicProductListItem & {
  description: string | null;
  meta_title: string | null;
  meta_description: string | null;
  categories: Array<
    Pick<
      PublicCategoryNode,
      | "id"
      | "name"
      | "slug"
      | "position"
      | "description"
      | "meta_title"
      | "meta_description"
    >
  >;
  images: PublicVariant["images"];
  variants: PublicVariant[];
};

export type StorefrontCartItem = {
  id: number;
  product_variant_id: number;
  qty: number;
  unit_price: string;
  line_total: string;
  product: { name: string; slug: string } | null;
  sku: string | null;
  attributes: PublicVariant["attributes"];
  thumbnail: PublicImage | null;
};

export type StorefrontCart = {
  id: number;
  currency: string;
  items: StorefrontCartItem[];
  subtotal: string;
};

export type ShippingRate = {
  id: number;
  price: string;
  min_order_amount: string | null;
  max_order_amount: string | null;
};

export type ShippingMethod = {
  id: number;
  code: string;
  name: string;
  rates: ShippingRate[];
};

export type GeoNode = {
  code: string;
  name: string;
};

export type GuestCheckoutBody = {
  shipping_address: {
    recipient_name: string;
    phone: string;
    province_code: string;
    district_code: string;
    ward_code: string;
    address_line: string;
  };
  shipping_method_id: number;
  shipping_rate_id: number;
  payment_method_code: "cod" | "vnpay";
  coupon_code?: string;
};

export type CheckoutBody = {
  customer_address_id?: number;
  shipping_address?: GuestCheckoutBody["shipping_address"];
  shipping_method_id: number;
  shipping_rate_id: number;
  payment_method_code: "cod" | "vnpay";
  coupon_code?: string;
};

export type CustomerProfile = {
  id: number;
  code: string;
  name: string;
  email: string;
  phone: string | null;
  status: string;
};

export type CustomerAddress = {
  id: number;
  label: string | null;
  recipient_name: string;
  phone: string;
  province_code: string;
  district_code: string;
  ward_code: string;
  address_line: string;
  postal_code: string | null;
  is_default: boolean;
};

export type CustomerAddressBody = {
  label?: string | null;
  recipient_name: string;
  phone: string;
  province_code: string;
  district_code: string;
  ward_code: string;
  address_line: string;
  postal_code?: string | null;
  is_default?: boolean;
};

export type CustomerOrderItem = {
  id?: number;
  sku: string;
  name: string;
  qty: number;
  unit_price: string;
  line_total: string;
};

export type CustomerOrder = {
  id: number;
  number: string;
  status: string;
  currency: string;
  subtotal: string;
  discount_total: string;
  shipping_total: string;
  tax_total: string;
  grand_total: string;
  shipping_address:
    | (GuestCheckoutBody["shipping_address"] & {
        postal_code?: string | null;
      })
    | null;
  items?: CustomerOrderItem[];
  status_history?: Array<{
    from_status: string | null;
    to_status: string;
    created_at: string | null;
  }>;
  created_at: string | null;
};

export type CheckoutCreated = {
  id: number;
  number: string;
  status: string;
  subtotal: string;
  discount_total: string;
  shipping_total: string;
  tax_total: string;
  grand_total: string;
  items: unknown[];
  next_action: string;
  payment: {
    provider: string;
    status: string;
    redirect_url?: string;
  };
  lookup_token?: string;
};

export type GuestOrderItem = {
  name: string;
  sku: string;
  qty: number;
  unit_price: string;
  line_total: string;
};

export type GuestOrder = {
  number: string;
  status: string;
  currency: string;
  subtotal: string;
  discount_total: string;
  shipping_total: string;
  tax_total: string;
  grand_total: string;
  items: GuestOrderItem[];
  shipping_address: GuestCheckoutBody["shipping_address"] | null;
};
