"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { Mail, MapPin, Phone, ShoppingCart } from "lucide-react";

import {
  DataTableShell,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/admin/layout/data-table";
import { EmptyState, LoadingState } from "@/components/admin/layout/empty-state";
import { StatCard } from "@/components/admin/layout/stat-card";
import { StatusBadge } from "@/components/admin/layout/status-badge";
import { Button } from "@/components/ui/button";
import { getCustomer } from "@/lib/api/customers/client";
import { customerErrorMessage } from "@/lib/api/customers/errors";
import { listOrders } from "@/lib/api/orders/client";

export function CustomerDetailPage({ customerId }: { customerId: number }) {
  const query = useQuery({
    queryKey: ["admin", "customers", customerId],
    queryFn: () => getCustomer(customerId),
    enabled: customerId > 0,
  });
  const orders = useQuery({
    queryKey: ["admin", "orders", "customer", customerId],
    queryFn: () => listOrders({ customer_id: customerId, page: 1, per_page: 10 }),
    enabled: customerId > 0,
  });

  if (customerId <= 0) {
    return <p className="text-sm text-[#ba1a1a]">Invalid customer ID.</p>;
  }

  if (query.isPending) {
    return <LoadingState />;
  }

  if (query.isError || !query.data?.data) {
    return (
      <p className="text-sm text-[#ba1a1a]">
        {customerErrorMessage(query.error)}
      </p>
    );
  }

  const customer = query.data.data;
  const addresses = customer.addresses ?? [];
  const orderRows = orders.data?.data ?? [];
  const orderTotal = orders.data?.meta.total ?? orderRows.length;

  return (
    <div className="space-y-6">
      <section className="relative overflow-hidden rounded-xl border border-[#e2e8f0] bg-white p-6 shadow-[0_4px_20px_rgba(15,23,42,0.04)]">
        <div className="pointer-events-none absolute -top-10 -right-10 h-40 w-40 rounded-full bg-[#ecf2ff] blur-3xl" />
        <div className="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div className="flex items-center gap-4">
            <div className="flex size-16 items-center justify-center rounded-2xl bg-[#ecf2ff] text-xl font-bold text-[#1f53c9]">
              {customer.name.slice(0, 1).toUpperCase()}
            </div>
            <div>
              <div className="flex flex-wrap items-center gap-2">
                <h1 className="text-2xl font-bold tracking-tight text-[#0f172a]">
                  {customer.name}
                </h1>
                <StatusBadge status={customer.status} />
              </div>
              <p className="mt-1 font-mono text-xs text-[#64748b]">
                {customer.code}
              </p>
              <div className="mt-2 flex flex-wrap gap-4 text-sm text-[#64748b]">
                <span className="inline-flex items-center gap-1">
                  <Mail className="size-3.5" /> {customer.email}
                </span>
                <span className="inline-flex items-center gap-1">
                  <Phone className="size-3.5" /> {customer.phone ?? "—"}
                </span>
              </div>
            </div>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline" size="sm">
              <Link href="/admin/customers">Back to directory</Link>
            </Button>
            <Button asChild size="sm">
              <Link href={`/admin/orders?customer_id=${customer.id}`}>
                View orders
              </Link>
            </Button>
          </div>
        </div>
      </section>

      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Orders" value={orderTotal} icon={<ShoppingCart className="size-5" />} />
        <StatCard
          label="Email verified"
          value={customer.email_verified_at ? "Yes" : "No"}
        />
        <StatCard
          label="Last login"
          value={
            customer.last_login_at
              ? new Date(customer.last_login_at).toLocaleDateString()
              : "—"
          }
        />
        <StatCard
          label="Member since"
          value={
            customer.created_at
              ? new Date(customer.created_at).toLocaleDateString()
              : "—"
          }
        />
      </section>

      <section className="grid gap-6 lg:grid-cols-12">
        <div className="lg:col-span-5">
          <DataTableShell>
            <div className="border-b border-[#e2e8f0] px-4 py-3">
              <h2 className="text-base font-bold text-[#0f172a]">Contact & addresses</h2>
              <p className="text-xs text-[#64748b]">
                {addresses.length} saved address{addresses.length === 1 ? "" : "es"}
              </p>
            </div>
            {addresses.length === 0 ? (
              <EmptyState title="No addresses" />
            ) : (
              <ul className="divide-y divide-[#e2e8f0]">
                {addresses.map((addr) => (
                  <li key={addr.id} className="flex gap-3 px-4 py-3 text-sm">
                    <MapPin className="mt-0.5 size-4 shrink-0 text-[#1f53c9]" />
                    <div>
                      <p className="font-semibold text-[#0f172a]">
                        {addr.label ?? "Address"}{" "}
                        {addr.is_default ? (
                          <span className="ml-1 text-[11px] font-semibold text-[#1f53c9]">
                            Default
                          </span>
                        ) : null}
                      </p>
                      <p>
                        {addr.recipient_name} · {addr.phone}
                      </p>
                      <p className="text-[#64748b]">{addr.address_line}</p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </DataTableShell>
        </div>
        <div className="lg:col-span-7">
          <DataTableShell>
            <div className="border-b border-[#e2e8f0] px-4 py-3">
              <h2 className="text-base font-bold text-[#0f172a]">Order history</h2>
            </div>
            {orders.isPending ? (
              <LoadingState />
            ) : orderRows.length === 0 ? (
              <EmptyState title="No orders for this customer" />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Number</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Total</TableHead>
                    <TableHead>Created</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {orderRows.map((order) => (
                    <TableRow key={order.id}>
                      <TableCell>
                        <Link
                          href={`/admin/orders/${order.id}`}
                          className="font-medium text-[#1f53c9] hover:underline"
                        >
                          {order.number}
                        </Link>
                      </TableCell>
                      <TableCell>
                        <StatusBadge status={String(order.status)} />
                      </TableCell>
                      <TableCell className="text-right tabular-nums">
                        {order.grand_total} {order.currency}
                      </TableCell>
                      <TableCell className="text-[#64748b]">
                        {order.created_at
                          ? new Date(order.created_at).toLocaleDateString()
                          : "—"}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </DataTableShell>
        </div>
      </section>
    </div>
  );
}
