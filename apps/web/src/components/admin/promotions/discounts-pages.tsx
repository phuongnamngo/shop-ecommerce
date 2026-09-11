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
  createDiscount,
  deleteDiscount,
  getDiscount,
  listDiscounts,
  updateDiscount,
} from "@/lib/api/promotions/discounts";
import { promotionErrorMessage } from "@/lib/api/promotions/errors";
import type {
  Discount,
  DiscountType,
  PromotionStatus,
} from "@/lib/api/promotions/types";

export function DiscountsListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ["admin", "promotions", "discounts", page, q],
    queryFn: () => listDiscounts({ page, per_page: 20, q: q || undefined }),
  });
  const editQuery = useQuery({
    queryKey: ["admin", "promotions", "discounts", editId],
    queryFn: () => getDiscount(editId!),
    enabled: editId != null,
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Discounts"
        description="Chiết khấu fixed/percentage dùng cho coupon checkout."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              + Add Discount
            </Button>
          ) : null
        }
      />

      <div className="flex max-w-sm gap-2">
        <Input
          placeholder="Search name or code"
          value={q}
          onChange={(e) => {
            setPage(1);
            setQ(e.target.value);
          }}
        />
      </div>

      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {promotionErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No discounts yet" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Type</TableHead>
                  <TableHead>Value</TableHead>
                  <TableHead>Rule</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((discount) => (
                  <TableRow key={discount.id}>
                    <TableCell className="font-medium">{discount.name}</TableCell>
                    <TableCell>{discount.type}</TableCell>
                    <TableCell className="font-mono text-xs">
                      {discount.value}
                    </TableCell>
                    <TableCell className="text-xs text-muted-foreground">
                      {discount.rule?.conditions.min_subtotal != null
                        ? `min ${discount.rule.conditions.min_subtotal}`
                        : "—"}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={discount.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditId(discount.id)}
                          >
                            Edit
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete discount"
                            onConfirm={async () => {
                              try {
                                await deleteDiscount(discount.id);
                                await queryClient.invalidateQueries({
                                  queryKey: ["admin", "promotions", "discounts"],
                                });
                              } catch (err) {
                                throw new Error(promotionErrorMessage(err));
                              }
                            }}
                          />
                        </div>
                      ) : (
                        <Button asChild variant="ghost" size="sm">
                          <Link
                            href={`/admin/promotions/discounts/${discount.id}`}
                          >
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
        title="Add Discount"
      >
        <DiscountFormFields
          mode="create"
          embedded
          initial={{
            name: "",
            type: "percentage",
            value: "10",
            status: "active",
            minSubtotal: "",
          }}
          onDone={() => setCreateOpen(false)}
        />
      </ModalForm>
      <ModalForm
        open={editId !== null}
        onOpenChange={(open) => {
          if (!open) setEditId(null);
        }}
        title="Edit Discount"
      >
        {editQuery.isPending ? (
          <p className="text-sm text-[#71717A]">Loading…</p>
        ) : editQuery.data?.data ? (
          <DiscountFormFields
            key={editQuery.data.data.id}
            mode="edit"
            embedded
            discountId={editQuery.data.data.id}
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

function toFormInitial(discount: Discount) {
  return {
    name: discount.name,
    type: discount.type,
    value: discount.value,
    status: discount.status,
    minSubtotal:
      discount.rule?.conditions.min_subtotal != null
        ? String(discount.rule.conditions.min_subtotal)
        : "",
  };
}

function DiscountFormFields({
  mode,
  discountId,
  initial,
  embedded = false,
  onDone,
}: {
  mode: "create" | "edit";
  discountId?: number;
  initial: {
    name: string;
    type: DiscountType;
    value: string;
    status: PromotionStatus;
    minSubtotal: string;
  };
  embedded?: boolean;
  onDone?: () => void;
}) {
  const queryClient = useQueryClient();
  const [name, setName] = useState(initial.name);
  const [type, setType] = useState<DiscountType>(initial.type);
  const [value, setValue] = useState(initial.value);
  const [status, setStatus] = useState<PromotionStatus>(initial.status);
  const [minSubtotal, setMinSubtotal] = useState(initial.minSubtotal);
  const [error, setError] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: async () => {
      const rule =
        minSubtotal.trim() === ""
          ? mode === "edit"
            ? null
            : undefined
          : { conditions: { min_subtotal: Number(minSubtotal) } };
      const body = {
        name,
        type,
        value: Number(value),
        status,
        ...(rule !== undefined ? { rule } : {}),
      };
      if (mode === "create") {
        return createDiscount({
          ...body,
          rule:
            minSubtotal.trim() === ""
              ? undefined
              : { conditions: { min_subtotal: Number(minSubtotal) } },
        });
      }
      return updateDiscount(discountId!, body);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "promotions", "discounts"],
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
        <Label htmlFor="discount-name">Name</Label>
        <Input
          id="discount-name"
          required
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </div>
      <div className="space-y-2">
        <Label>Type</Label>
        <Select
          value={type}
          onValueChange={(v) => setType(v as DiscountType)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="percentage">percentage</SelectItem>
            <SelectItem value="fixed">fixed</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div className="space-y-2">
        <Label htmlFor="discount-value">Value</Label>
        <Input
          id="discount-value"
          required
          type="number"
          step="0.01"
          min="0"
          value={value}
          onChange={(e) => setValue(e.target.value)}
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="min-subtotal">Min subtotal (optional)</Label>
        <Input
          id="min-subtotal"
          type="number"
          min="0"
          value={minSubtotal}
          onChange={(e) => setMinSubtotal(e.target.value)}
          placeholder="Leave empty for no rule"
        />
      </div>
      <div className="space-y-2">
        <Label>Status</Label>
        <Select
          value={status}
          onValueChange={(v) => setStatus(v as PromotionStatus)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="active">active</SelectItem>
            <SelectItem value="inactive">inactive</SelectItem>
          </SelectContent>
        </Select>
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
        <CardTitle>{mode === "create" ? "Add discount" : "Edit discount"}</CardTitle>
      </CardHeader>
      <CardContent>{form}</CardContent>
    </Card>
  );
}

export function DiscountCreatePage() {
  return (
    <RequirePromotionsManage redirectTo="/admin/promotions/discounts">
      <DiscountFormFields
        mode="create"
        initial={{
          name: "",
          type: "percentage",
          value: "10",
          status: "active",
          minSubtotal: "",
        }}
      />
    </RequirePromotionsManage>
  );
}

export function DiscountEditPage({ discountId }: { discountId: number }) {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;
  const existing = useQuery({
    queryKey: ["admin", "promotions", "discounts", discountId],
    queryFn: () => getDiscount(discountId),
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

  const discount = existing.data.data;
  if (!manage) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{discount.name}</CardTitle>
          <CardDescription>Chỉ xem (staff).</CardDescription>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p>
            Type: {discount.type} / Value: {discount.value}
          </p>
          <p>Status: {discount.status}</p>
          <Button asChild variant="outline">
            <Link href="/admin/promotions/discounts">Quay lại</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <DiscountFormFields
      key={discount.id}
      mode="edit"
      discountId={discountId}
      initial={toFormInitial(discount)}
    />
  );
}
