"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { listCustomers } from "@/lib/api/customers/client";
import { customerErrorMessage } from "@/lib/api/customers/errors";

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

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  function onSearch(e: FormEvent) {
    e.preventDefault();
    setPage(1);
    setQApplied(q.trim());
  }

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-xl font-semibold">Customers</h1>
        <p className="text-sm text-muted-foreground">
          Danh sách khách hàng — lọc status và tìm name/email/phone.
        </p>
      </div>

      <Card>
        <CardContent className="space-y-4 pt-6">
          <div className="flex flex-wrap gap-2">
            <Select
              value={status}
              onValueChange={(v) => {
                setStatus(v);
                setPage(1);
              }}
            >
              <SelectTrigger className="w-40">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">all</SelectItem>
                <SelectItem value="active">active</SelectItem>
                <SelectItem value="inactive">inactive</SelectItem>
                <SelectItem value="banned">banned</SelectItem>
              </SelectContent>
            </Select>
            <form className="flex min-w-[16rem] flex-1 gap-2" onSubmit={onSearch}>
              <Input
                placeholder="Tìm name / email / phone…"
                value={q}
                onChange={(e) => setQ(e.target.value)}
              />
              <Button type="submit" variant="outline">
                Tìm
              </Button>
            </form>
          </div>

          {query.isPending ? (
            <p className="text-sm text-muted-foreground">Đang tải…</p>
          ) : query.isError ? (
            <p className="text-sm text-destructive">
              {customerErrorMessage(query.error)}
            </p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Code</TableHead>
                    <TableHead>Name</TableHead>
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
                      <TableCell className="font-mono text-xs">
                        {customer.code}
                      </TableCell>
                      <TableCell className="font-medium">
                        {customer.name}
                      </TableCell>
                      <TableCell>{customer.email}</TableCell>
                      <TableCell>{customer.phone ?? "—"}</TableCell>
                      <TableCell>{customer.status}</TableCell>
                      <TableCell className="text-muted-foreground text-xs">
                        {customer.created_at
                          ? new Date(customer.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button asChild variant="outline" size="sm">
                          <Link href={`/admin/customers/${customer.id}`}>
                            Xem
                          </Link>
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {meta ? (
                <div className="flex items-center justify-between text-sm">
                  <span>
                    Trang {meta.current_page}/{meta.last_page} · {meta.total}
                  </span>
                  <div className="flex gap-2">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={page <= 1}
                      onClick={() => setPage((p) => p - 1)}
                    >
                      Trước
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      disabled={page >= meta.last_page}
                      onClick={() => setPage((p) => p + 1)}
                    >
                      Sau
                    </Button>
                  </div>
                </div>
              ) : null}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
