"use client";

import { useState } from "react";
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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { listAdminActivity } from "@/lib/api/activity/client";
import type { ActivityChange, AdminActivity } from "@/lib/api/activity/types";
import { ApiError } from "@/lib/api/client";

function activityErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }
  return "Không tải được nhật ký.";
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined || value === "") {
    return "—";
  }
  if (
    typeof value === "string" ||
    typeof value === "number" ||
    typeof value === "boolean"
  ) {
    return String(value);
  }
  return JSON.stringify(value);
}

function summarizeChanges(
  event: string | null,
  changes: Record<string, ActivityChange>,
): string {
  const parts = Object.entries(changes).map(([field, change]) => {
    if (event === "created") {
      return `${field}: ${formatValue(change.new)}`;
    }
    return `${field}: ${formatValue(change.old)} → ${formatValue(change.new)}`;
  });

  return parts.join(", ") || "—";
}

function subjectLabel(row: AdminActivity): string {
  const name = row.log_name ?? "—";
  return row.subject_id === null ? name : `${name} #${row.subject_id}`;
}

export function ActivityPage() {
  const [page, setPage] = useState(1);
  const [logName, setLogName] = useState("all");

  const query = useQuery({
    queryKey: ["admin", "activity", page, logName],
    queryFn: () =>
      listAdminActivity({
        page,
        per_page: 20,
        log_name: logName === "all" ? undefined : logName,
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader title="Activity" />

      <FilterBar>
        <Select
          value={logName}
          onValueChange={(value) => {
            setPage(1);
            setLogName(value);
          }}
        >
          <SelectTrigger className="w-[220px]">
            <SelectValue placeholder="Log name" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">all</SelectItem>
            <SelectItem value="order">order</SelectItem>
            <SelectItem value="product">product</SelectItem>
            <SelectItem value="product_variant">product_variant</SelectItem>
            <SelectItem value="stock_movement">stock_movement</SelectItem>
          </SelectContent>
        </Select>
      </FilterBar>

      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {activityErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="Chưa có hoạt động." />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Thời gian</TableHead>
                  <TableHead>Event</TableHead>
                  <TableHead>Subject</TableHead>
                  <TableHead>Causer</TableHead>
                  <TableHead>Tóm tắt</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((row) => (
                  <TableRow key={row.id}>
                    <TableCell className="whitespace-nowrap tabular-nums">
                      {row.created_at
                        ? new Date(row.created_at).toLocaleString()
                        : "—"}
                    </TableCell>
                    <TableCell>{row.event ?? "—"}</TableCell>
                    <TableCell className="font-mono text-xs">
                      {subjectLabel(row)}
                    </TableCell>
                    <TableCell>{row.causer_name ?? "—"}</TableCell>
                    <TableCell className="max-w-xl truncate text-sm text-[#64748b]">
                      {summarizeChanges(row.event, row.changes)}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
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
