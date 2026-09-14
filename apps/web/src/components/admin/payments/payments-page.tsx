"use client";

import { useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import {
  AdminPagination,
  DataTableShell,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/admin/layout/data-table";
import { EmptyState, LoadingState } from "@/components/admin/layout/empty-state";
import { FilterBar } from "@/components/admin/layout/filter-bar";
import { PageHeader } from "@/components/admin/layout/page-header";
import { StatCard } from "@/components/admin/layout/stat-card";
import { StatusBadge } from "@/components/admin/layout/status-badge";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { listPaymentTransactions } from "@/lib/api/payments/client";
import { paymentErrorMessage } from "@/lib/api/payments/errors";

export function PaymentsPage() {
  const [page, setPage] = useState(1);
  const [provider, setProvider] = useState("all");
  const [status, setStatus] = useState("all");
  const [refundStatus, setRefundStatus] = useState("all");
  const [q, setQ] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");

  const query = useQuery({
    queryKey: [
      "admin",
      "payments",
      page,
      provider,
      status,
      refundStatus,
      q,
      from,
      to,
    ],
    queryFn: () =>
      listPaymentTransactions({
        page,
        per_page: 20,
        provider: provider === "all" ? undefined : provider,
        status: status === "all" ? undefined : status,
        refund_status: refundStatus === "all" ? undefined : refundStatus,
        q: q.trim() || undefined,
        from: from || undefined,
        to: to || undefined,
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Payments"
        description="Đối soát giao dịch và hoàn tiền theo đơn."
      />

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          label="Đã thu"
          value={meta?.sum_succeeded ?? "—"}
          tone="success"
        />
        <StatCard
          label="Đã hoàn"
          value={meta?.sum_refunded ?? "—"}
          tone="primary"
        />
        <StatCard
          label="Hoàn pending"
          value={meta?.count_pending ?? "—"}
          tone="warning"
        />
        <StatCard
          label="Hoàn failed"
          value={meta?.count_failed ?? "—"}
          tone="danger"
        />
      </div>

      <DataTableShell>
        <FilterBar>
          <Input
            value={q}
            onChange={(e) => {
              setQ(e.target.value);
              setPage(1);
            }}
            placeholder="Số đơn…"
            className="h-9 w-40"
          />
          <Select
            value={provider}
            onValueChange={(value) => {
              setProvider(value);
              setPage(1);
            }}
          >
            <SelectTrigger className="h-9 w-36">
              <SelectValue placeholder="Provider" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Mọi cổng</SelectItem>
              <SelectItem value="cod">COD</SelectItem>
              <SelectItem value="vnpay">VNPay</SelectItem>
            </SelectContent>
          </Select>
          <Select
            value={status}
            onValueChange={(value) => {
              setStatus(value);
              setPage(1);
            }}
          >
            <SelectTrigger className="h-9 w-40">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Mọi status</SelectItem>
              <SelectItem value="pending">pending</SelectItem>
              <SelectItem value="succeeded">succeeded</SelectItem>
              <SelectItem value="failed">failed</SelectItem>
              <SelectItem value="expired">expired</SelectItem>
            </SelectContent>
          </Select>
          <Select
            value={refundStatus}
            onValueChange={(value) => {
              setRefundStatus(value);
              setPage(1);
            }}
          >
            <SelectTrigger className="h-9 w-44">
              <SelectValue placeholder="Refund" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Mọi hoàn</SelectItem>
              <SelectItem value="pending">pending</SelectItem>
              <SelectItem value="failed">failed</SelectItem>
              <SelectItem value="succeeded">succeeded</SelectItem>
            </SelectContent>
          </Select>
          <Input
            type="date"
            value={from}
            onChange={(e) => {
              setFrom(e.target.value);
              setPage(1);
            }}
            className="h-9 w-40"
            aria-label="From"
          />
          <Input
            type="date"
            value={to}
            onChange={(e) => {
              setTo(e.target.value);
              setPage(1);
            }}
            className="h-9 w-40"
            aria-label="To"
          />
        </FilterBar>

        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {paymentErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="Không có giao dịch." />
        ) : (
          <>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Order</TableHead>
                    <TableHead>Provider</TableHead>
                    <TableHead>Amount</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Refund</TableHead>
                    <TableHead>Created</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((row) => (
                    <TableRow key={row.id}>
                      <TableCell>
                        <Link
                          href={`/admin/orders/${row.order_id}`}
                          className="font-medium text-[#1f53c9] hover:underline"
                        >
                          {row.order_number ?? `#${row.order_id}`}
                        </Link>
                      </TableCell>
                      <TableCell>{row.provider}</TableCell>
                      <TableCell className="tabular-nums">{row.amount}</TableCell>
                      <TableCell>
                        <StatusBadge status={row.status} />
                      </TableCell>
                      <TableCell>
                        {row.refund ? (
                          <StatusBadge status={String(row.refund.status)} />
                        ) : (
                          "—"
                        )}
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {row.created_at
                          ? new Date(row.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
            {meta ? (
              <AdminPagination
                page={page}
                lastPage={meta.last_page}
                total={meta.total}
                onPrev={() => setPage((p) => p - 1)}
                onNext={() => setPage((p) => p + 1)}
              />
            ) : null}
          </>
        )}
      </DataTableShell>
    </div>
  );
}
