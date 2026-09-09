"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Textarea } from "@/components/ui/textarea";
import { useAdminMe } from "@/hooks/use-admin-me";
import { canManageOrders } from "@/lib/admin/can-manage-orders";
import { nextOrderStatuses } from "@/lib/admin/order-transitions";
import {
  createOrderShipment,
  getOrder,
  updateOrderStatus,
} from "@/lib/api/orders/client";
import { orderErrorMessage } from "@/lib/api/orders/errors";

export function OrderDetailPage({ orderId }: { orderId: number }) {
  const queryClient = useQueryClient();
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManageOrders(me.data.roles) : false;

  const orderQuery = useQuery({
    queryKey: ["admin", "orders", orderId],
    queryFn: () => getOrder(orderId),
  });

  const [note, setNote] = useState("");
  const [tracking, setTracking] = useState("");
  const [carrier, setCarrier] = useState("");
  const [error, setError] = useState<string | null>(null);

  const invalidate = async () => {
    await queryClient.invalidateQueries({
      queryKey: ["admin", "orders", orderId],
    });
    await queryClient.invalidateQueries({ queryKey: ["admin", "orders"] });
  };

  const transition = useMutation({
    mutationFn: (status: string) =>
      updateOrderStatus(orderId, {
        status,
        note: note.trim() || null,
      }),
    onSuccess: async () => {
      setError(null);
      setNote("");
      await invalidate();
    },
    onError: (err) => setError(orderErrorMessage(err)),
  });

  const ship = useMutation({
    mutationFn: () =>
      createOrderShipment(orderId, {
        tracking_number: tracking.trim(),
        carrier_code: carrier.trim() || null,
      }),
    onSuccess: async () => {
      setError(null);
      setTracking("");
      setCarrier("");
      await invalidate();
    },
    onError: (err) => setError(orderErrorMessage(err)),
  });

  if (orderQuery.isPending) {
    return <p className="text-sm text-muted-foreground">Đang tải…</p>;
  }
  if (orderQuery.isError || !orderQuery.data?.data) {
    return (
      <p className="text-sm text-destructive">
        {orderErrorMessage(orderQuery.error)}
      </p>
    );
  }

  const order = orderQuery.data.data;
  const next = nextOrderStatuses(order.status);
  const address = order.shipping_address;
  const items = order.items ?? [];
  const history = order.status_history ?? [];
  const shipments = order.shipments ?? [];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">{order.number}</h1>
          <p className="text-sm text-muted-foreground">
            Status: {order.status} · Customer #{order.customer_id ?? "—"}
          </p>
        </div>
        <Button asChild variant="outline">
          <Link href="/admin/orders">Danh sách</Link>
        </Button>
      </div>

      {error ? (
        <p className="text-sm text-destructive" role="alert">
          {error}
        </p>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle>Tóm tắt</CardTitle>
          {!manage ? (
            <CardDescription>Chỉ xem (staff).</CardDescription>
          ) : null}
        </CardHeader>
        <CardContent className="grid gap-2 text-sm sm:grid-cols-2">
          <p>
            Subtotal: {order.subtotal} {order.currency}
          </p>
          <p>Discount: {order.discount_total}</p>
          <p>Shipping: {order.shipping_total}</p>
          <p>Tax: {order.tax_total}</p>
          <p className="font-medium">
            Grand total: {order.grand_total} {order.currency}
          </p>
          <p>
            Created:{" "}
            {order.created_at
              ? new Date(order.created_at).toLocaleString()
              : "—"}
          </p>
          {address ? (
            <div className="sm:col-span-2 rounded-md border p-3">
              <p className="mb-1 font-medium">Shipping address</p>
              <p>
                {address.recipient_name} · {address.phone}
              </p>
              <p>{address.address_line}</p>
              <p className="text-muted-foreground">
                {address.ward_code} / {address.district_code} /{" "}
                {address.province_code}
              </p>
            </div>
          ) : null}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Line items</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>SKU</TableHead>
                <TableHead>Name</TableHead>
                <TableHead>Qty</TableHead>
                <TableHead>Unit</TableHead>
                <TableHead>Line</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {items.map((item) => (
                <TableRow key={item.id}>
                  <TableCell>{item.sku}</TableCell>
                  <TableCell>{item.name}</TableCell>
                  <TableCell>{item.qty}</TableCell>
                  <TableCell>{item.unit_price}</TableCell>
                  <TableCell>{item.line_total}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Status history</CardTitle>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>From</TableHead>
                <TableHead>To</TableHead>
                <TableHead>At</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {history.map((row, idx) => (
                <TableRow key={`${row.to_status}-${row.created_at}-${idx}`}>
                  <TableCell>{row.from_status ?? "—"}</TableCell>
                  <TableCell>{row.to_status}</TableCell>
                  <TableCell className="text-xs text-muted-foreground">
                    {row.created_at
                      ? new Date(row.created_at).toLocaleString()
                      : "—"}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Shipments</CardTitle>
        </CardHeader>
        <CardContent>
          {shipments.length === 0 ? (
            <p className="text-sm text-muted-foreground">Chưa có shipment.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Tracking</TableHead>
                  <TableHead>Carrier</TableHead>
                  <TableHead>Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {shipments.map((s) => (
                  <TableRow key={s.id}>
                    <TableCell>{s.tracking_number}</TableCell>
                    <TableCell>{s.carrier_code ?? "—"}</TableCell>
                    <TableCell>{s.status}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {manage ? (
        <Card>
          <CardHeader>
            <CardTitle>Actions</CardTitle>
            <CardDescription>
              Chỉ hiện transition hợp lệ. Ship qua form khi fulfilling (không
              PATCH shipped).
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="note">Note (optional)</Label>
              <Textarea
                id="note"
                value={note}
                onChange={(e) => setNote(e.target.value)}
                placeholder="Ghi chú khi đổi status…"
              />
            </div>
            <div className="flex flex-wrap gap-2">
              {next.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                  Không còn transition từ status hiện tại.
                </p>
              ) : (
                next.map((status) => (
                  <Button
                    key={status}
                    type="button"
                    variant={status === "cancelled" ? "destructive" : "default"}
                    disabled={transition.isPending}
                    onClick={() => {
                      setError(null);
                      void transition.mutateAsync(status);
                    }}
                  >
                    → {status}
                  </Button>
                ))
              )}
            </div>

            {order.status === "fulfilling" ? (
              <form
                className="space-y-3 rounded-md border p-3"
                onSubmit={(e: FormEvent) => {
                  e.preventDefault();
                  setError(null);
                  void ship.mutateAsync();
                }}
              >
                <p className="text-sm font-medium">Tạo shipment (full-ship)</p>
                <div className="space-y-2">
                  <Label htmlFor="tracking">Tracking number</Label>
                  <Input
                    id="tracking"
                    required
                    value={tracking}
                    onChange={(e) => setTracking(e.target.value)}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="carrier">Carrier code (optional)</Label>
                  <Input
                    id="carrier"
                    value={carrier}
                    onChange={(e) => setCarrier(e.target.value)}
                  />
                </div>
                <Button type="submit" disabled={ship.isPending || !tracking.trim()}>
                  {ship.isPending ? "Đang ship…" : "Ship order"}
                </Button>
              </form>
            ) : null}
          </CardContent>
        </Card>
      ) : null}
    </div>
  );
}
