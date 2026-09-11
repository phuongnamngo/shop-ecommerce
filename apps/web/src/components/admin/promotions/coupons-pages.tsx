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
  createCoupon,
  deleteCoupon,
  getCoupon,
  listCoupons,
  updateCoupon,
} from "@/lib/api/promotions/coupons";
import { listDiscounts } from "@/lib/api/promotions/discounts";
import { promotionErrorMessage } from "@/lib/api/promotions/errors";
import type { Coupon, PromotionStatus } from "@/lib/api/promotions/types";

export function CouponsListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ["admin", "promotions", "coupons", page, q],
    queryFn: () => listCoupons({ page, per_page: 20, q: q || undefined }),
  });
  const editQuery = useQuery({
    queryKey: ["admin", "promotions", "coupons", editId],
    queryFn: () => getCoupon(editId!),
    enabled: editId != null,
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Coupons"
        description="Mã giảm giá gắn discount; apply tại checkout."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              + Add Coupon
            </Button>
          ) : null
        }
      />

      <div className="flex max-w-sm gap-2">
        <Input
          placeholder="Search code"
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
          <EmptyState title="No coupons yet" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Code</TableHead>
                  <TableHead>Discount</TableHead>
                  <TableHead>Uses</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((coupon) => (
                  <TableRow key={coupon.id}>
                    <TableCell className="font-mono font-medium">
                      {coupon.code}
                    </TableCell>
                    <TableCell className="text-sm">
                      {coupon.discount?.name ?? `#${coupon.discount_id}`}
                    </TableCell>
                    <TableCell className="font-mono text-xs">
                      {coupon.used_count}
                      {coupon.max_uses != null ? ` / ${coupon.max_uses}` : ""}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={coupon.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditId(coupon.id)}
                          >
                            Edit
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete coupon"
                            onConfirm={async () => {
                              try {
                                await deleteCoupon(coupon.id);
                                await queryClient.invalidateQueries({
                                  queryKey: ["admin", "promotions", "coupons"],
                                });
                              } catch (err) {
                                throw new Error(promotionErrorMessage(err));
                              }
                            }}
                          />
                        </div>
                      ) : (
                        <Button asChild variant="ghost" size="sm">
                          <Link href={`/admin/promotions/coupons/${coupon.id}`}>
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
        title="Add Coupon"
      >
        <CouponFormFields
          mode="create"
          embedded
          initial={{
            code: "",
            discountId: "",
            maxUses: "",
            maxUsesPerCustomer: "",
            status: "active",
          }}
          onDone={() => setCreateOpen(false)}
        />
      </ModalForm>
      <ModalForm
        open={editId !== null}
        onOpenChange={(open) => {
          if (!open) setEditId(null);
        }}
        title="Edit Coupon"
      >
        {editQuery.isPending ? (
          <p className="text-sm text-[#71717A]">Loading…</p>
        ) : editQuery.data?.data ? (
          <CouponFormFields
            key={editQuery.data.data.id}
            mode="edit"
            embedded
            couponId={editQuery.data.data.id}
            initial={toCouponInitial(editQuery.data.data)}
            usedCount={editQuery.data.data.used_count}
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

function toCouponInitial(coupon: Coupon) {
  return {
    code: coupon.code,
    discountId: String(coupon.discount_id),
    maxUses: coupon.max_uses != null ? String(coupon.max_uses) : "",
    maxUsesPerCustomer:
      coupon.max_uses_per_customer != null
        ? String(coupon.max_uses_per_customer)
        : "",
    status: coupon.status,
  };
}

function CouponFormFields({
  mode,
  couponId,
  initial,
  usedCount,
  embedded = false,
  onDone,
}: {
  mode: "create" | "edit";
  couponId?: number;
  initial: {
    code: string;
    discountId: string;
    maxUses: string;
    maxUsesPerCustomer: string;
    status: PromotionStatus;
  };
  usedCount?: number;
  embedded?: boolean;
  onDone?: () => void;
}) {
  const queryClient = useQueryClient();
  const [code, setCode] = useState(initial.code);
  const [discountId, setDiscountId] = useState(initial.discountId);
  const [maxUses, setMaxUses] = useState(initial.maxUses);
  const [maxUsesPerCustomer, setMaxUsesPerCustomer] = useState(
    initial.maxUsesPerCustomer,
  );
  const [status, setStatus] = useState<PromotionStatus>(initial.status);
  const [error, setError] = useState<string | null>(null);

  const discounts = useQuery({
    queryKey: ["admin", "promotions", "discounts", "options"],
    queryFn: () => listDiscounts({ per_page: 100, status: "active" }),
  });

  const save = useMutation({
    mutationFn: async () => {
      const body = {
        code,
        discount_id: Number(discountId),
        max_uses: maxUses.trim() === "" ? null : Number(maxUses),
        max_uses_per_customer:
          maxUsesPerCustomer.trim() === ""
            ? null
            : Number(maxUsesPerCustomer),
        status,
      };
      if (mode === "create") {
        return createCoupon(body);
      }
      return updateCoupon(couponId!, body);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "promotions", "coupons"],
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

  const discountOptions = discounts.data?.data ?? [];

  const form = (
    <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <div className="space-y-2">
        <Label htmlFor="coupon-code">Code</Label>
        <Input
          id="coupon-code"
          required
          value={code}
          onChange={(e) => setCode(e.target.value)}
        />
      </div>
      <div className="space-y-2">
        <Label>Discount</Label>
        <Select value={discountId} onValueChange={setDiscountId}>
          <SelectTrigger>
            <SelectValue placeholder="Select discount" />
          </SelectTrigger>
          <SelectContent>
            {discountOptions.map((d) => (
              <SelectItem key={d.id} value={String(d.id)}>
                {d.name} ({d.type} {d.value})
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
      <div className="space-y-2">
        <Label htmlFor="max-uses">Max uses (optional)</Label>
        <Input
          id="max-uses"
          type="number"
          min="1"
          value={maxUses}
          onChange={(e) => setMaxUses(e.target.value)}
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="max-per-customer">Max uses / customer (optional)</Label>
        <Input
          id="max-per-customer"
          type="number"
          min="1"
          value={maxUsesPerCustomer}
          onChange={(e) => setMaxUsesPerCustomer(e.target.value)}
        />
      </div>
      {mode === "edit" && usedCount != null ? (
        <p className="text-sm text-muted-foreground">
          Used count: {usedCount} (read-only)
        </p>
      ) : null}
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
        <Button
          type="submit"
          disabled={save.isPending || !discountId}
        >
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
        <CardTitle>{mode === "create" ? "Add coupon" : "Edit coupon"}</CardTitle>
      </CardHeader>
      <CardContent>{form}</CardContent>
    </Card>
  );
}

export function CouponCreatePage() {
  return (
    <RequirePromotionsManage redirectTo="/admin/promotions/coupons">
      <CouponFormFields
        mode="create"
        initial={{
          code: "",
          discountId: "",
          maxUses: "",
          maxUsesPerCustomer: "",
          status: "active",
        }}
      />
    </RequirePromotionsManage>
  );
}

export function CouponEditPage({ couponId }: { couponId: number }) {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;
  const existing = useQuery({
    queryKey: ["admin", "promotions", "coupons", couponId],
    queryFn: () => getCoupon(couponId),
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

  const coupon = existing.data.data;
  if (!manage) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{coupon.code}</CardTitle>
          <CardDescription>Chỉ xem (staff).</CardDescription>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p>Discount: {coupon.discount?.name ?? coupon.discount_id}</p>
          <p>
            Uses: {coupon.used_count}
            {coupon.max_uses != null ? ` / ${coupon.max_uses}` : ""}
          </p>
          <p>Status: {coupon.status}</p>
          <Button asChild variant="outline">
            <Link href="/admin/promotions/coupons">Quay lại</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <CouponFormFields
      key={coupon.id}
      mode="edit"
      couponId={couponId}
      initial={toCouponInitial(coupon)}
      usedCount={coupon.used_count}
    />
  );
}
