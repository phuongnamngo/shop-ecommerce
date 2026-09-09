"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { UserRound } from "lucide-react";

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
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { listCustomers } from "@/lib/api/customers/client";
import { customerErrorMessage } from "@/lib/api/customers/errors";
import { cn } from "@/lib/utils";

const STATUS_TABS = ["all", "active", "inactive", "banned"] as const;

export function CustomersListPage() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState("all");
  const [q, setQ] = useState("");
  const [qApplied, setQApplied] = useState("");

  const query = useQuery({
    queryKey: ["admin", "customers", page, status, qApplied],
    queryFn: () =>
      listCustomers({
        page,
        per_page: 20,
        status: status === "all" ? undefined : status,
        q: qApplied || undefined,
      }),
  });
  const totals = useQuery({
    queryKey: ["admin", "customers", "totals"],
    queryFn: async () => {
      const [all, active, inactive, banned] = await Promise.all([
        listCustomers({ page: 1, per_page: 1 }),
        listCustomers({ page: 1, per_page: 1, status: "active" }),
        listCustomers({ page: 1, per_page: 1, status: "inactive" }),
        listCustomers({ page: 1, per_page: 1, status: "banned" }),
      ]);
      return {
        all: all.meta.total,
        active: active.meta.total,
        inactive: inactive.meta.total,
        banned: banned.meta.total,
      };
    },
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  function onSearch(e: FormEvent) {
    e.preventDefault();
    setPage(1);
    setQApplied(q.trim());
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Customers Directory"
        description="Storefront accounts — filter by status or search name, email, phone."
      />

      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Total" value={totals.data?.all ?? "—"} />
        <StatCard label="Active" value={totals.data?.active ?? "—"} tone="success" />
        <StatCard label="Inactive" value={totals.data?.inactive ?? "—"} />
        <StatCard label="Banned" value={totals.data?.banned ?? "—"} tone="danger" />
      </section>

      <div className="flex flex-wrap gap-1 rounded-xl border border-[#e2e8f0] bg-white p-1">
        {STATUS_TABS.map((tab) => (
          <button
            key={tab}
            type="button"
            onClick={() => {
              setStatus(tab);
              setPage(1);
            }}
            className={cn(
              "rounded-lg px-3 py-1.5 text-[13px] font-semibold capitalize transition-colors duration-150",
              status === tab
                ? "bg-[#ecf2ff] text-[#1f53c9]"
                : "text-[#64748b] hover:text-[#0f172a]",
            )}
          >
            {tab}
          </button>
        ))}
      </div>

      <DataTableShell>
        <FilterBar>
          <form className="flex min-w-[16rem] flex-1 gap-2" onSubmit={onSearch}>
            <Input
              placeholder="Search name / email / phone…"
              value={q}
              onChange={(e) => setQ(e.target.value)}
            />
            <Button type="submit" variant="outline">
              Search
            </Button>
          </form>
        </FilterBar>

        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {customerErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No customers match this filter" />
        ) : (
          <>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Customer</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Phone</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((customer) => (
                    <TableRow key={customer.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <div className="flex size-9 items-center justify-center rounded-full bg-[#ecf2ff] text-[#1f53c9]">
                            <UserRound className="size-4" />
                          </div>
                          <div>
                            <div className="font-medium">{customer.name}</div>
                            <div className="font-mono text-[11px] text-[#64748b]">
                              {customer.code}
                            </div>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>{customer.email}</TableCell>
                      <TableCell>{customer.phone ?? "—"}</TableCell>
                      <TableCell>
                        <StatusBadge status={customer.status} />
                      </TableCell>
                      <TableCell className="text-[13px] text-[#64748b]">
                        {customer.created_at
                          ? new Date(customer.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button asChild variant="outline" size="sm">
                          <Link href={`/admin/customers/${customer.id}`}>
                            View 360
                          </Link>
                        </Button>
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
