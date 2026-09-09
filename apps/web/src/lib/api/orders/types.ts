export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type OrderStatus =
  | "pending"
  | "paid"
  | "fulfilling"
  | "shipped"
  | "completed"
  | "cancelled";

export type OrderShipment = {
  id: number;
  tracking_number: string;
  carrier_code: string | null;
  status: string;
};

export type OrderItem = {
  id: number;
  sku: string;
  name: string;
  qty: number;
  unit_price: string | number;
  line_total: string | number;
};

export type OrderStatusHistory = {
  from_status: string | null;
  to_status: string;
  created_at: string | null;
};

export type ShippingAddress = {
  recipient_name: string;
  phone: string;
  province_code: string;
  district_code: string;
  ward_code: string;
  address_line: string;
  postal_code?: string | null;
};

export type AdminOrder = {
  id: number;
  number: string;
  status: OrderStatus | string;
  currency: string;
  subtotal: string | number;
  discount_total: string | number;
  shipping_total: string | number;
  tax_total: string | number;
  grand_total: string | number;
  customer_id: number | null;
  shipping_address: ShippingAddress | null;
  items?: OrderItem[];
  status_history?: OrderStatusHistory[];
  shipments?: OrderShipment[];
  created_at: string | null;
};
