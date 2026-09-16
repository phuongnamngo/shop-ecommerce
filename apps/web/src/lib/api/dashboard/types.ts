export type PeriodMetrics = {
  order_count: number;
  revenue: string;
};

export type RevenueSeriesPoint = {
  date: string;
  order_count: number;
  revenue: string;
};

export type TopSkuRow = {
  sku: string;
  name: string;
  qty: number;
  revenue: string;
};

export type LowStockRow = {
  sku: string;
  name: string;
  warehouse_code: string;
  warehouse_name: string;
  available_qty: number;
};

export type DashboardMetrics = {
  today: PeriodMetrics;
  month: PeriodMetrics;
  currency: string;
  as_of: string;
  revenue_series: RevenueSeriesPoint[];
  top_skus: TopSkuRow[];
  low_stock: LowStockRow[];
  low_stock_count: number;
};
