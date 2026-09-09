"use client";

import { useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";

import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
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
import { listOrders } from "@/lib/api/orders/client";
import { orderErrorMessage } from "@/lib/api/orders/errors";

export function OrdersListPage() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState("all");

  const query = useQuery({
    queryKey: ["admin", "orders", page, status],
    queryFn: () =>
      listOrders({
        page,
        per_page: 15,
        status: status === "all" ? undefined : status,
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-xl font-semibold">Orders</h1>
        <p className="text-sm text-muted-foreground">
          Danh sách đơn hàng — lọc theo status.
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
                <SelectItem value="pending">pending</SelectItem>
                <SelectItem value="paid">paid</SelectItem>
                <SelectItem value="fulfilling">fulfilling</SelectItem>
                <SelectItem value="shipped">shipped</SelectItem>
                <SelectItem value="completed">completed</SelectItem>
                <SelectItem value="cancelled">cancelled</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {query.isPending ? (
            <p className="text-sm text-muted-foreground">Đang tải…</p>
          ) : query.isError ? (
            <p className="text-sm text-destructive">
              {orderErrorMessage(query.error)}
            </p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Number</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Grand total</TableHead>
                    <TableHead>Customer ID</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((order) => (
                    <TableRow key={order.id}>
                      <TableCell className="font-medium">
                        {order.number}
                      </TableCell>
                      <TableCell>{order.status}</TableCell>
                      <TableCell>
                        {order.grand_total} {order.currency}
                      </TableCell>
                      <TableCell>{order.customer_id ?? "—"}</TableCell>
                      <TableCell className="text-muted-foreground text-xs">
                        {order.created_at
                          ? new Date(order.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button asChild variant="outline" size="sm">
                          <Link href={`/admin/orders/${order.id}`}>Xem</Link>
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
