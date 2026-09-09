"use client";

import { FormEvent, useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { Button } from "@/components/ui/button";
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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
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
import { canManageInventory } from "@/lib/admin/can-manage-inventory";
import {
  createStockMovement,
  listStockItems,
  listWarehouses,
} from "@/lib/api/inventory/client";
import { inventoryErrorMessage } from "@/lib/api/inventory/errors";
import type {
  StockItem,
  StockMovementType,
} from "@/lib/api/inventory/types";

function movementQtyValid(type: StockMovementType, raw: string): boolean {
  if (raw.trim() === "" || !/^-?\d+$/.test(raw.trim())) {
    return false;
  }
  const qty = Number.parseInt(raw, 10);
  if (!Number.isFinite(qty)) return false;
  if (type === "adjustment") return qty !== 0;
  return qty >= 1;
}

export function InventoryPage() {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManageInventory(me.data.roles) : false;
  const queryClient = useQueryClient();

  const [page, setPage] = useState(1);
  const [warehouseFilter, setWarehouseFilter] = useState("all");
  const warehouseId =
    warehouseFilter === "all"
      ? undefined
      : Number.parseInt(warehouseFilter, 10);

  const warehousesQuery = useQuery({
    queryKey: ["admin", "inventory", "warehouses"],
    queryFn: () => listWarehouses({ per_page: 100 }),
  });

  const stockQuery = useQuery({
    queryKey: [
      "admin",
      "inventory",
      "stock-items",
      page,
      warehouseId ?? null,
    ],
    queryFn: () =>
      listStockItems({
        page,
        per_page: 20,
        warehouse_id: warehouseId,
      }),
  });

  const [dialogItem, setDialogItem] = useState<StockItem | null>(null);
  const [type, setType] = useState<StockMovementType>("receipt");
  const [qty, setQty] = useState("1");
  const [note, setNote] = useState("");
  const [dialogError, setDialogError] = useState<string | null>(null);

  const qtyOk = useMemo(() => movementQtyValid(type, qty), [type, qty]);

  const movement = useMutation({
    mutationFn: () => {
      if (!dialogItem) {
        throw new Error("Missing stock row");
      }
      return createStockMovement({
        warehouse_id: dialogItem.warehouse_id,
        product_variant_id: dialogItem.product_variant_id,
        type,
        qty: Number.parseInt(qty, 10),
        note: note.trim() || null,
      });
    },
    onSuccess: async () => {
      setDialogError(null);
      setDialogItem(null);
      setType("receipt");
      setQty("1");
      setNote("");
      await queryClient.invalidateQueries({
        queryKey: ["admin", "inventory", "stock-items"],
      });
    },
    onError: (err) => setDialogError(inventoryErrorMessage(err)),
  });

  function openDialog(item: StockItem) {
    setDialogItem(item);
    setType("receipt");
    setQty("1");
    setNote("");
    setDialogError(null);
  }

  function onSubmitMovement(e: FormEvent) {
    e.preventDefault();
    if (!qtyOk || !dialogItem) return;
    movement.mutate();
  }

  const rows = stockQuery.data?.data ?? [];
  const meta = stockQuery.data?.meta;
  const warehouses = warehousesQuery.data?.data ?? [];
  const lowStock = rows.filter((item) => item.available_qty > 0 && item.available_qty <= 5);
  const outStock = rows.filter((item) => item.available_qty <= 0);
  const healthy = rows.filter((item) => item.available_qty > 5);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Inventory & Stock"
        description="On-hand, reserved, and available quantity by warehouse. Adjustments use receipt / issue / adjustment movements."
      />

      <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="SKU rows" value={meta?.total ?? "—"} />
        <StatCard label="In stock" value={healthy.length} tone="success" hint="On this page · available > 5" />
        <StatCard label="Low stock" value={lowStock.length} tone="warning" hint="Available 1–5" />
        <StatCard label="Out of stock" value={outStock.length} tone="danger" hint="Available ≤ 0" />
      </section>

      <DataTableShell>
        <FilterBar>
          <Select
            value={warehouseFilter}
            onValueChange={(v) => {
              setWarehouseFilter(v);
              setPage(1);
            }}
          >
            <SelectTrigger className="w-56">
              <SelectValue placeholder="Warehouse" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">all warehouses</SelectItem>
              {warehouses.map((wh) => (
                <SelectItem key={wh.id} value={String(wh.id)}>
                  {wh.code} — {wh.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </FilterBar>

        {warehousesQuery.isError ? (
          <p className="px-4 py-3 text-sm text-[#ba1a1a]">
            {inventoryErrorMessage(warehousesQuery.error)}
          </p>
        ) : null}

        {stockQuery.isPending ? (
          <LoadingState />
        ) : stockQuery.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {inventoryErrorMessage(stockQuery.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No stock rows" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Warehouse</TableHead>
                  <TableHead>SKU</TableHead>
                  <TableHead>Product</TableHead>
                  <TableHead className="text-right">On hand</TableHead>
                  <TableHead className="text-right">Reserved</TableHead>
                  <TableHead className="text-right">Available</TableHead>
                  {manage ? (
                    <TableHead className="text-right">Actions</TableHead>
                  ) : null}
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((item) => (
                  <TableRow key={item.id}>
                    <TableCell className="text-sm">
                      {item.warehouse.code}
                      <span className="mt-0.5 block text-xs text-[#64748b]">
                        {item.warehouse.name}
                      </span>
                    </TableCell>
                    <TableCell className="font-mono text-xs">
                      {item.variant.sku}
                    </TableCell>
                    <TableCell>
                      {item.variant.product_name ?? "—"}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      {item.qty_on_hand}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      {item.qty_reserved}
                    </TableCell>
                    <TableCell className="text-right tabular-nums">
                      <div className="flex flex-col items-end gap-1">
                        {item.available_qty}
                        <StatusBadge
                          status={
                            item.available_qty <= 0
                              ? "out of stock"
                              : item.available_qty <= 5
                                ? "low stock"
                                : "in stock"
                          }
                        />
                      </div>
                    </TableCell>
                    {manage ? (
                      <TableCell className="text-right">
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          onClick={() => openDialog(item)}
                        >
                          Adjust
                        </Button>
                      </TableCell>
                    ) : null}
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

      <Dialog
        open={dialogItem !== null}
        onOpenChange={(open) => {
          if (!open) {
            setDialogItem(null);
            setDialogError(null);
          }
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Adjust stock</DialogTitle>
            <DialogDescription>
              Movement on an existing stock row — this does not create a new row.
            </DialogDescription>
          </DialogHeader>
          {dialogItem ? (
            <form className="space-y-4" onSubmit={onSubmitMovement}>
              <dl className="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                  <dt className="text-muted-foreground">Warehouse</dt>
                  <dd>
                    {dialogItem.warehouse.code} — {dialogItem.warehouse.name}
                  </dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">SKU</dt>
                  <dd className="font-mono text-xs">{dialogItem.variant.sku}</dd>
                </div>
                <div className="sm:col-span-2">
                  <dt className="text-muted-foreground">Product</dt>
                  <dd>{dialogItem.variant.product_name ?? "—"}</dd>
                </div>
              </dl>

              <div className="space-y-2">
                <Label>Type</Label>
                <Select
                  value={type}
                  onValueChange={(v) => setType(v as StockMovementType)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="receipt">receipt</SelectItem>
                    <SelectItem value="issue">issue</SelectItem>
                    <SelectItem value="adjustment">adjustment</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="qty">Qty</Label>
                <Input
                  id="qty"
                  type="number"
                  inputMode="numeric"
                  value={qty}
                  onChange={(e) => setQty(e.target.value)}
                />
                <p className="text-xs text-muted-foreground">
                  receipt/issue: số nguyên ≥ 1; adjustment: số nguyên ≠ 0 (âm
                  được).
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="note">Note</Label>
                <Input
                  id="note"
                  value={note}
                  onChange={(e) => setNote(e.target.value)}
                  placeholder="Optional"
                />
              </div>

              {dialogError ? (
                <p className="text-sm text-destructive" role="alert">
                  {dialogError}
                </p>
              ) : null}

              <DialogFooter>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setDialogItem(null)}
                  disabled={movement.isPending}
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  disabled={!qtyOk || movement.isPending}
                >
                  {movement.isPending ? "Saving…" : "Save"}
                </Button>
              </DialogFooter>
            </form>
          ) : null}
        </DialogContent>
      </Dialog>
    </div>
  );
}
