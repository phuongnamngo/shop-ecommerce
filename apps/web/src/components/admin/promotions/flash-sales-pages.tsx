"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { ConfirmDeleteButton } from "@/components/admin/catalog/confirm-delete-button";
import { RequirePromotionsManage } from "@/components/admin/require-promotions-manage";
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
import { ModalForm } from "@/components/admin/layout/modal-form";
import { PageHeader } from "@/components/admin/layout/page-header";
import { StatusBadge } from "@/components/admin/layout/status-badge";
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useAdminMe } from "@/hooks/use-admin-me";
import { canManagePromotions } from "@/lib/admin/can-manage-promotions";
import {
  createFlashSale,
  deleteFlashSale,
  getFlashSale,
  listFlashSales,
  updateFlashSale,
} from "@/lib/api/promotions/flash-sales";
import { promotionErrorMessage } from "@/lib/api/promotions/errors";
import type { FlashSale, FlashSaleStatus } from "@/lib/api/promotions/types";

type ItemRow = {
  product_variant_id: string;
  sale_price: string;
  qty_cap: string;
};

function pad(n: number): string {
  return String(n).padStart(2, "0");
}

function toDatetimeLocal(iso: string | null | undefined): string {
  if (!iso) return "";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return "";
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fromDatetimeLocal(value: string): string {
  return new Date(value).toISOString();
}

function defaultWindow(): { starts_at: string; ends_at: string } {
  const start = new Date();
  const end = new Date(start.getTime() + 24 * 60 * 60 * 1000);
  return {
    starts_at: toDatetimeLocal(start.toISOString()),
    ends_at: toDatetimeLocal(end.toISOString()),
  };
}

function emptyItem(): ItemRow {
  return { product_variant_id: "", sale_price: "", qty_cap: "" };
}

export function FlashSalesListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ["admin", "promotions", "flash-sales", page, q],
    queryFn: () => listFlashSales({ page, per_page: 20, q: q || undefined }),
  });
  const editQuery = useQuery({
    queryKey: ["admin", "promotions", "flash-sales", editId],
    queryFn: () => getFlashSale(editId!),
    enabled: editId != null,
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Flash sales"
        description="Giá sale theo variant trong khung giờ, có qty cap."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              Add flash sale
            </Button>
          ) : null
        }
      />
      <div className="flex gap-2">
        <Input
          value={q}
          onChange={(e) => {
            setQ(e.target.value);
            setPage(1);
          }}
          placeholder="Search name or code"
          className="max-w-sm"
        />
      </div>
      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="text-sm text-destructive">
            {promotionErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="Chưa có flash sale" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Window</TableHead>
                  <TableHead>Items</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((sale) => (
                  <TableRow key={sale.id}>
                    <TableCell className="font-medium">{sale.name}</TableCell>
                    <TableCell className="text-xs text-muted-foreground">
                      {toDatetimeLocal(sale.starts_at)} → {toDatetimeLocal(sale.ends_at)}
                    </TableCell>
                    <TableCell>{sale.items.length}</TableCell>
                    <TableCell>
                      <StatusBadge status={sale.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditId(sale.id)}
                          >
                            Edit
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete flash sale"
                            onConfirm={async () => {
                              try {
                                await deleteFlashSale(sale.id);
                                await queryClient.invalidateQueries({
                                  queryKey: ["admin", "promotions", "flash-sales"],
                                });
                              } catch (err) {
                                throw new Error(promotionErrorMessage(err));
                              }
                            }}
                          />
                        </div>
                      ) : (
                        <Button asChild variant="ghost" size="sm">
                          <Link href={`/admin/promotions/flash-sales/${sale.id}`}>
                            View
                          </Link>
                        </Button>
                      )}
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

      <ModalForm
        open={createOpen}
        onOpenChange={setCreateOpen}
        title="Add flash sale"
      >
        <FlashSaleFormFields
          mode="create"
          embedded
          initial={emptyForm()}
          onDone={() => setCreateOpen(false)}
        />
      </ModalForm>
      <ModalForm
        open={editId !== null}
        onOpenChange={(open) => {
          if (!open) setEditId(null);
        }}
        title="Edit flash sale"
      >
        {editQuery.isPending ? (
          <p className="text-sm text-[#71717A]">Loading…</p>
        ) : editQuery.data?.data ? (
          <FlashSaleFormFields
            key={editQuery.data.data.id}
            mode="edit"
            embedded
            saleId={editQuery.data.data.id}
            initial={toFormInitial(editQuery.data.data)}
            onDone={() => setEditId(null)}
          />
        ) : (
          <p className="text-sm text-[#ba1a1a]">
            {promotionErrorMessage(editQuery.error)}
          </p>
        )}
      </ModalForm>
    </div>
  );
}

function emptyForm() {
  const window = defaultWindow();
  return {
    name: "",
    status: "scheduled" as FlashSaleStatus,
    starts_at: window.starts_at,
    ends_at: window.ends_at,
    items: [emptyItem()],
  };
}

function toFormInitial(sale: FlashSale) {
  return {
    name: sale.name,
    status: sale.status,
    starts_at: toDatetimeLocal(sale.starts_at),
    ends_at: toDatetimeLocal(sale.ends_at),
    items:
      sale.items.length === 0
        ? [emptyItem()]
        : sale.items.map((item) => ({
            product_variant_id: String(item.product_variant_id),
            sale_price: item.sale_price,
            qty_cap: item.qty_cap == null ? "" : String(item.qty_cap),
          })),
  };
}

function FlashSaleFormFields({
  mode,
  saleId,
  initial,
  embedded = false,
  onDone,
}: {
  mode: "create" | "edit";
  saleId?: number;
  initial: {
    name: string;
    status: FlashSaleStatus;
    starts_at: string;
    ends_at: string;
    items: ItemRow[];
  };
  embedded?: boolean;
  onDone?: () => void;
}) {
  const queryClient = useQueryClient();
  const [name, setName] = useState(initial.name);
  const [status, setStatus] = useState<FlashSaleStatus>(initial.status);
  const [startsAt, setStartsAt] = useState(initial.starts_at);
  const [endsAt, setEndsAt] = useState(initial.ends_at);
  const [items, setItems] = useState<ItemRow[]>(initial.items);
  const [error, setError] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: async () => {
      const payload = {
        name,
        status,
        starts_at: fromDatetimeLocal(startsAt),
        ends_at: fromDatetimeLocal(endsAt),
        items: items
          .filter((row) => row.product_variant_id.trim() !== "")
          .map((row) => ({
            product_variant_id: Number(row.product_variant_id),
            sale_price: Number(row.sale_price),
            qty_cap: row.qty_cap.trim() === "" ? null : Number(row.qty_cap),
          })),
      };
      if (mode === "create") {
        return createFlashSale(payload);
      }
      return updateFlashSale(saleId!, payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "promotions", "flash-sales"],
      });
      onDone?.();
    },
    onError: (err) => setError(promotionErrorMessage(err)),
  });

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    await save.mutateAsync();
  }

  const form = (
    <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <div className="space-y-2">
        <Label htmlFor="flash-name">Name</Label>
        <Input
          id="flash-name"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </div>
      <div className="grid gap-3 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="flash-starts">Starts at</Label>
          <Input
            id="flash-starts"
            required
            type="datetime-local"
            value={startsAt}
            onChange={(e) => setStartsAt(e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="flash-ends">Ends at</Label>
          <Input
            id="flash-ends"
            required
            type="datetime-local"
            value={endsAt}
            onChange={(e) => setEndsAt(e.target.value)}
          />
        </div>
      </div>
      <div className="space-y-2">
        <Label>Status</Label>
        <Select
          value={status}
          onValueChange={(v) => setStatus(v as FlashSaleStatus)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="scheduled">scheduled</SelectItem>
            <SelectItem value="active">active</SelectItem>
            <SelectItem value="ended">ended</SelectItem>
            <SelectItem value="cancelled">cancelled</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div className="space-y-2">
        <div className="flex items-center justify-between">
          <Label>Items</Label>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => setItems((rows) => [...rows, emptyItem()])}
          >
            Add item
          </Button>
        </div>
        <div className="space-y-2">
          {items.map((row, index) => (
            <div key={index} className="grid gap-2 sm:grid-cols-[1fr_1fr_1fr_auto]">
              <Input
                required
                type="number"
                min="1"
                placeholder="Variant ID"
                value={row.product_variant_id}
                onChange={(e) =>
                  setItems((rows) =>
                    rows.map((current, i) =>
                      i === index
                        ? { ...current, product_variant_id: e.target.value }
                        : current,
                    ),
                  )
                }
              />
              <Input
                required
                type="number"
                min="1"
                step="0.01"
                placeholder="Sale price"
                value={row.sale_price}
                onChange={(e) =>
                  setItems((rows) =>
                    rows.map((current, i) =>
                      i === index
                        ? { ...current, sale_price: e.target.value }
                        : current,
                    ),
                  )
                }
              />
              <Input
                type="number"
                min="1"
                placeholder="Qty cap"
                value={row.qty_cap}
                onChange={(e) =>
                  setItems((rows) =>
                    rows.map((current, i) =>
                      i === index ? { ...current, qty_cap: e.target.value } : current,
                    ),
                  )
                }
              />
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() =>
                  setItems((rows) =>
                    rows.length === 1
                      ? [emptyItem()]
                      : rows.filter((_, i) => i !== index),
                  )
                }
              >
                Remove
              </Button>
            </div>
          ))}
        </div>
      </div>
      {error ? (
        <p className="text-sm text-destructive" role="alert">
          {error}
        </p>
      ) : null}
      <div className="flex flex-wrap gap-2">
        <Button type="submit" disabled={save.isPending}>
          {save.isPending ? "Saving…" : "Save"}
        </Button>
        {embedded ? (
          <Button type="button" variant="outline" onClick={() => onDone?.()}>
            Cancel
          </Button>
        ) : null}
      </div>
    </form>
  );

  if (embedded) {
    return form;
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{mode === "create" ? "Add flash sale" : "Edit flash sale"}</CardTitle>
      </CardHeader>
      <CardContent>{form}</CardContent>
    </Card>
  );
}

export function FlashSaleCreatePage() {
  return (
    <RequirePromotionsManage redirectTo="/admin/promotions/flash-sales">
      <FlashSaleFormFields mode="create" initial={emptyForm()} />
    </RequirePromotionsManage>
  );
}

export function FlashSaleEditPage({ saleId }: { saleId: number }) {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;
  const existing = useQuery({
    queryKey: ["admin", "promotions", "flash-sales", saleId],
    queryFn: () => getFlashSale(saleId),
  });

  if (existing.isPending) {
    return <p className="text-sm text-muted-foreground">Đang tải…</p>;
  }
  if (existing.isError || !existing.data?.data) {
    return (
      <p className="text-sm text-destructive">
        {promotionErrorMessage(existing.error)}
      </p>
    );
  }

  const sale = existing.data.data;
  if (!manage) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{sale.name}</CardTitle>
          <CardDescription>Chỉ xem (staff).</CardDescription>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p>Status: {sale.status}</p>
          <p>
            Window: {toDatetimeLocal(sale.starts_at)} → {toDatetimeLocal(sale.ends_at)}
          </p>
          <ul className="list-disc pl-5">
            {sale.items.map((item) => (
              <li key={item.id}>
                Variant {item.product_variant_id}: {item.sale_price}
                {item.qty_cap != null
                  ? ` (còn ${item.qty_remaining ?? 0}/${item.qty_cap})`
                  : ""}
              </li>
            ))}
          </ul>
          <Button asChild variant="outline">
            <Link href="/admin/promotions/flash-sales">Quay lại</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <FlashSaleFormFields
      key={sale.id}
      mode="edit"
      saleId={saleId}
      initial={toFormInitial(sale)}
    />
  );
}
