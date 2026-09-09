export type PageMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type Warehouse = {
  id: number;
  code: string;
  name: string;
  is_default: boolean;
  status: string;
};

export type StockMovementType = "receipt" | "issue" | "adjustment";

export type StockItem = {
  id: number;
  warehouse_id: number;
  product_variant_id: number;
  qty_on_hand: number;
  qty_reserved: number;
  available_qty: number;
  warehouse: { id: number; code: string; name: string };
  variant: {
    id: number;
    sku: string;
    product_id: number;
    product_name: string | null;
  };
};
