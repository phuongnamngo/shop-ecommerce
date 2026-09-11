"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

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
import { PageHeader } from "@/components/admin/layout/page-header";
import { StatusBadge } from "@/components/admin/layout/status-badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useAdminMe } from "@/hooks/use-admin-me";
import { canManageReviews } from "@/lib/admin/can-manage-reviews";
import {
  getAdminReview,
  listAdminReviews,
  patchAdminReview,
} from "@/lib/api/reviews/client";
import { reviewErrorMessage } from "@/lib/api/reviews/errors";
import type { ReviewStatus } from "@/lib/api/reviews/types";
import { cn } from "@/lib/utils";

const STATUS_TABS = ["all", "pending", "approved", "rejected"] as const;

export function ReviewsListPage() {
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState<(typeof STATUS_TABS)[number]>("pending");
  const [q, setQ] = useState("");
  const [submittedQ, setSubmittedQ] = useState("");

  const query = useQuery({
    queryKey: ["admin", "reviews", page, status, submittedQ],
    queryFn: () =>
      listAdminReviews({
        page,
        per_page: 20,
        q: submittedQ || undefined,
        status: status === "all" ? undefined : status,
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  function onSearch(event: FormEvent) {
    event.preventDefault();
    setPage(1);
    setSubmittedQ(q.trim());
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Reviews"
        description="Duyệt đánh giá verified purchase trước khi hiện trên PDP."
      />

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

      <form onSubmit={onSearch} className="flex max-w-md gap-2">
        <Input
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Tên sản phẩm, tên hoặc email khách"
        />
        <Button type="submit" variant="outline">
          Search
        </Button>
      </form>

      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {reviewErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No reviews in this filter" />
        ) : (
          <>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Product</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Rating</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Created</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((review) => (
                    <TableRow key={review.id}>
                      <TableCell className="font-medium">
                        {review.product?.name ?? "—"}
                      </TableCell>
                      <TableCell className="text-[13px]">
                        <div>{review.customer?.name ?? "—"}</div>
                        <div className="text-[#64748b]">
                          {review.customer?.email ?? ""}
                        </div>
                      </TableCell>
                      <TableCell>{review.rating}</TableCell>
                      <TableCell>
                        <StatusBadge status={review.status} />
                      </TableCell>
                      <TableCell className="text-[13px] text-[#64748b]">
                        {review.created_at
                          ? new Date(review.created_at).toLocaleString()
                          : "—"}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button asChild variant="ghost" size="sm">
                          <Link href={`/admin/catalog/reviews/${review.id}`}>
                            View
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

export function ReviewDetailPage({ id }: { id: number }) {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManageReviews(me.data.roles) : false;

  const query = useQuery({
    queryKey: ["admin", "reviews", id],
    queryFn: () => getAdminReview(id),
  });

  const moderate = useMutation({
    mutationFn: (status: Extract<ReviewStatus, "approved" | "rejected">) =>
      patchAdminReview(id, { status }),
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ["admin", "reviews"] });
    },
  });

  const review = query.data?.data;

  return (
    <div className="space-y-6">
      <PageHeader
        title={review ? `Review #${review.id}` : "Review"}
        description="Chi tiết đánh giá — staff xem được, chỉ admin/super_admin duyệt."
        actions={
          <Button asChild variant="outline">
            <Link href="/admin/catalog/reviews">Back</Link>
          </Button>
        }
      />

      {query.isPending ? (
        <LoadingState />
      ) : query.isError ? (
        <p className="text-sm text-[#ba1a1a]">{reviewErrorMessage(query.error)}</p>
      ) : review ? (
        <div className="space-y-4 rounded-xl border border-[#e2e8f0] bg-white p-6">
          <p>
            <span className="text-[#64748b]">Status: </span>
            <StatusBadge status={review.status} />
          </p>
          <p>
            <span className="text-[#64748b]">Product: </span>
            {review.product?.name ?? "—"}
          </p>
          <p>
            <span className="text-[#64748b]">Customer: </span>
            {review.customer?.name} ({review.customer?.email})
          </p>
          <p>
            <span className="text-[#64748b]">Rating: </span>
            {review.rating} / 5
          </p>
          {review.body ? (
            <p className="whitespace-pre-wrap text-sm">{review.body}</p>
          ) : null}
          {(review.images ?? []).length > 0 ? (
            <ul className="flex flex-wrap gap-2">
              {(review.images ?? []).map((image) => (
                <li key={image.id}>
                  {/* eslint-disable-next-line @next/next/no-img-element */}
                  <img
                    src={image.thumbnail_url || image.url}
                    alt=""
                    className="h-24 w-24 rounded-lg object-cover"
                  />
                </li>
              ))}
            </ul>
          ) : null}
          {moderate.isError ? (
            <p className="text-sm text-[#ba1a1a]">
              {reviewErrorMessage(moderate.error)}
            </p>
          ) : null}
          {manage ? (
            <div className="flex gap-2">
              <Button
                type="button"
                disabled={moderate.isPending || review.status === "approved"}
                onClick={() => moderate.mutate("approved")}
              >
                Approve
              </Button>
              <Button
                type="button"
                variant="outline"
                disabled={moderate.isPending || review.status === "rejected"}
                onClick={() => moderate.mutate("rejected")}
              >
                Reject
              </Button>
            </div>
          ) : null}
        </div>
      ) : (
        <EmptyState title="Review not found" />
      )}
    </div>
  );
}
