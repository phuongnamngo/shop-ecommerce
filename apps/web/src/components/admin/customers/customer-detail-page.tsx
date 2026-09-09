"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { getCustomer } from "@/lib/api/customers/client";
import { customerErrorMessage } from "@/lib/api/customers/errors";

export function CustomerDetailPage({ customerId }: { customerId: number }) {
  const query = useQuery({
    queryKey: ["admin", "customers", customerId],
    queryFn: () => getCustomer(customerId),
    enabled: customerId > 0,
  });

  if (customerId <= 0) {
    return (
      <p className="text-sm text-destructive">ID khách hàng không hợp lệ.</p>
    );
  }

  if (query.isPending) {
    return <p className="text-sm text-muted-foreground">Đang tải…</p>;
  }

  if (query.isError || !query.data?.data) {
    return (
      <p className="text-sm text-destructive">
        {customerErrorMessage(query.error)}
      </p>
    );
  }

  const customer = query.data.data;
  const addresses = customer.addresses ?? [];

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">{customer.name}</h1>
          <p className="text-sm text-muted-foreground">
            {customer.code} · {customer.email}
          </p>
        </div>
        <div className="flex gap-2">
          <Button asChild variant="outline" size="sm">
            <Link href="/admin/customers">← Danh sách</Link>
          </Button>
          {customer.id > 0 ? (
            <Button asChild size="sm">
              <Link href={`/admin/orders?customer_id=${customer.id}`}>
                Xem đơn hàng
              </Link>
            </Button>
          ) : null}
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Hồ sơ</CardTitle>
          <CardDescription>Thông tin tài khoản khách (read-only).</CardDescription>
        </CardHeader>
        <CardContent>
          <dl className="grid gap-3 text-sm sm:grid-cols-2">
            <div>
              <dt className="text-muted-foreground">Status</dt>
              <dd>{customer.status}</dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Phone</dt>
              <dd>{customer.phone ?? "—"}</dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Email verified</dt>
              <dd>
                {customer.email_verified_at
                  ? new Date(customer.email_verified_at).toLocaleString()
                  : "—"}
              </dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Last login</dt>
              <dd>
                {customer.last_login_at
                  ? new Date(customer.last_login_at).toLocaleString()
                  : "—"}
              </dd>
            </div>
            <div>
              <dt className="text-muted-foreground">Created</dt>
              <dd>
                {customer.created_at
                  ? new Date(customer.created_at).toLocaleString()
                  : "—"}
              </dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Địa chỉ</CardTitle>
          <CardDescription>
            Địa chỉ active (chưa soft-delete) — {addresses.length} mục.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {addresses.length === 0 ? (
            <p className="text-sm text-muted-foreground">Chưa có địa chỉ.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Label</TableHead>
                  <TableHead>Recipient</TableHead>
                  <TableHead>Phone</TableHead>
                  <TableHead>Address</TableHead>
                  <TableHead>Default</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {addresses.map((addr) => (
                  <TableRow key={addr.id}>
                    <TableCell>{addr.label ?? "—"}</TableCell>
                    <TableCell>{addr.recipient_name}</TableCell>
                    <TableCell>{addr.phone}</TableCell>
                    <TableCell className="max-w-md text-sm">
                      {addr.address_line}
                      <span className="mt-0.5 block text-xs text-muted-foreground">
                        {addr.ward_code}/{addr.district_code}/
                        {addr.province_code}
                        {addr.postal_code ? ` · ${addr.postal_code}` : ""}
                      </span>
                    </TableCell>
                    <TableCell>{addr.is_default ? "yes" : "—"}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
