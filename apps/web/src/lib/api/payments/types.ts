export type PaymentTxnStatus =
  | "pending"
  | "succeeded"
  | "failed"
  | "expired"
  | string;

export type RefundStatus = "pending" | "succeeded" | "failed" | "rejected";

export type PaymentTotals = {
  sum_succeeded: string;
  sum_refunded: string;
  count_pending: number;
  count_failed: number;
};

export type AdminRefund = {
  id: number;
  payment_transaction_id: number;
  amount: string;
  status: RefundStatus | string;
  reason: string | null;
  provider_refund_id: string | null;
  idempotency_key: string;
  requested_by_admin_id: number | null;
  reviewed_by_admin_id: number | null;
  reviewed_at: string | null;
  created_at: string | null;
};

export type PaymentTransactionRow = {
  id: number;
  order_id: number;
  order_number?: string | null;
  provider: string;
  provider_txn_id: string | null;
  idempotency_key: string;
  amount: string;
  status: PaymentTxnStatus;
  refund?: AdminRefund | null;
  created_at: string | null;
};

export type OrderPayment = {
  id: number;
  provider: string;
  amount: string;
  status: PaymentTxnStatus;
  provider_txn_id: string | null;
  idempotency_key: string;
};
