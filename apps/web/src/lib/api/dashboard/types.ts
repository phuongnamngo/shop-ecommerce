export type PeriodMetrics = {
  order_count: number;
  revenue: string;
};

export type DashboardMetrics = {
  today: PeriodMetrics;
  month: PeriodMetrics;
  currency: string;
  as_of: string;
};
