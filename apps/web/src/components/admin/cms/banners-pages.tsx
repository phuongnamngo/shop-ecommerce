"use client";

import { FormEvent, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { ConfirmDeleteButton } from "@/components/admin/catalog/confirm-delete-button";
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
import { ModalForm } from "@/components/admin/layout/modal-form";
import { PageHeader } from "@/components/admin/layout/page-header";
import { StatusBadge } from "@/components/admin/layout/status-badge";
import { Button } from "@/components/ui/button";
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
import { canManageCms } from "@/lib/admin/can-manage-cms";
import { cmsErrorMessage } from "@/lib/api/cms/errors";
import {
  createCmsBanner,
  deleteCmsBanner,
  getCmsBanner,
  listCmsBanners,
  updateCmsBanner,
} from "@/lib/api/cms/banners";
import type {
  CmsBanner,
  CmsBannerPlacement,
  CmsBannerStatus,
} from "@/lib/api/cms/types";
import { uploadCatalogImage } from "@/lib/api/catalog/products";

export function CmsBannersListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage = me.isSuccess && me.data ? canManageCms(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [placement, setPlacement] = useState<string>("all");
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ["admin", "cms", "banners", page, placement],
    queryFn: () =>
      listCmsBanners({
        page,
        per_page: 20,
        placement: placement === "all" ? undefined : placement,
      }),
  });
  const editQuery = useQuery({
    queryKey: ["admin", "cms", "banners", editId],
    queryFn: () => getCmsBanner(editId!),
    enabled: editId != null,
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Banner Management"
        description="Promo bar and homepage hero placements."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              + Add Banner
            </Button>
          ) : null
        }
      />

      <FilterBar>
        <Select
          value={placement}
          onValueChange={(value) => {
            setPage(1);
            setPlacement(value);
          }}
        >
          <SelectTrigger className="w-[220px]">
            <SelectValue placeholder="Placement" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All placements</SelectItem>
            <SelectItem value="promo_bar">promo_bar</SelectItem>
            <SelectItem value="homepage_hero">homepage_hero</SelectItem>
          </SelectContent>
        </Select>
      </FilterBar>

      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {cmsErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No banners yet" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Placement</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((banner) => (
                  <TableRow key={banner.id}>
                    <TableCell className="font-medium">
                      {banner.title ?? "—"}
                    </TableCell>
                    <TableCell className="font-mono text-xs">
                      {banner.placement}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={banner.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditId(banner.id)}
                          >
                            Edit
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete banner"
                            onConfirm={async () => {
                              await deleteCmsBanner(banner.id);
                              await queryClient.invalidateQueries({
                                queryKey: ["admin", "cms", "banners"],
                              });
                            }}
                          />
                        </div>
                      ) : (
                        <span className="text-xs text-muted-foreground">
                          Read only
                        </span>
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
        title="Add Banner"
      >
        <BannerFormFields
          mode="create"
          initial={{
            placement: "promo_bar",
            title: "",
            image_url: "",
            link_url: "/products",
            starts_at: "",
            ends_at: "",
            sort: 0,
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
        title="Edit Banner"
      >
        {editQuery.isPending ? (
          <p className="text-sm text-[#71717A]">Loading…</p>
        ) : editQuery.data?.data ? (
          <BannerFormFields
            key={editQuery.data.data.id}
            mode="edit"
            bannerId={editQuery.data.data.id}
            initial={toFormInitial(editQuery.data.data)}
            onDone={() => setEditId(null)}
          />
        ) : (
          <p className="text-sm text-[#ba1a1a]">
            {cmsErrorMessage(editQuery.error)}
          </p>
        )}
      </ModalForm>
    </div>
  );
}

function toFormInitial(banner: CmsBanner) {
  return {
    placement: banner.placement,
    title: banner.title ?? "",
    image_url: banner.image_url ?? "",
    link_url: banner.link_url ?? "",
    starts_at: banner.starts_at ?? "",
    ends_at: banner.ends_at ?? "",
    sort: banner.sort,
    status: banner.status,
  };
}

function BannerFormFields({
  mode,
  bannerId,
  initial,
  onDone,
}: {
  mode: "create" | "edit";
  bannerId?: number;
  initial: {
    placement: CmsBannerPlacement;
    title: string;
    image_url: string;
    link_url: string;
    starts_at: string;
    ends_at: string;
    sort: number;
    status: CmsBannerStatus;
  };
  onDone?: () => void;
}) {
  const queryClient = useQueryClient();
  const [placement, setPlacement] = useState<CmsBannerPlacement>(
    initial.placement,
  );
  const [title, setTitle] = useState(initial.title);
  const [imageUrl, setImageUrl] = useState(initial.image_url);
  const [linkUrl, setLinkUrl] = useState(initial.link_url);
  const [startsAt, setStartsAt] = useState(initial.starts_at);
  const [endsAt, setEndsAt] = useState(initial.ends_at);
  const [sort, setSort] = useState(String(initial.sort));
  const [status, setStatus] = useState<CmsBannerStatus>(initial.status);
  const [error, setError] = useState<string | null>(null);
  const [uploading, setUploading] = useState(false);

  const save = useMutation({
    mutationFn: async () => {
      const body = {
        placement,
        title: title || null,
        image_url: imageUrl || null,
        link_url: linkUrl || null,
        starts_at: startsAt || null,
        ends_at: endsAt || null,
        sort: Number(sort) || 0,
        status,
      };
      if (mode === "create") {
        return createCmsBanner(body);
      }
      return updateCmsBanner(bannerId!, body);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "cms", "banners"],
      });
      onDone?.();
    },
    onError: (err) => setError(cmsErrorMessage(err)),
  });

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    await save.mutateAsync();
  }

  return (
    <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <div className="space-y-2">
        <Label>Placement</Label>
        <Select
          value={placement}
          onValueChange={(v) => setPlacement(v as CmsBannerPlacement)}
        >
          <SelectTrigger>
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="promo_bar">promo_bar</SelectItem>
            <SelectItem value="homepage_hero">homepage_hero</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <div className="space-y-2">
        <Label htmlFor="banner-title">Title</Label>
        <Input
          id="banner-title"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="banner-image">Image URL</Label>
        <Input
          id="banner-image"
          value={imageUrl}
          onChange={(e) => setImageUrl(e.target.value)}
        />
        <Input
          type="file"
          accept="image/jpeg,image/jpg,image/png,image/webp"
          disabled={uploading}
          onChange={(e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            void (async () => {
              setUploading(true);
              setError(null);
              try {
                const uploaded = await uploadCatalogImage(file);
                setImageUrl(uploaded.data.url ?? uploaded.data.path);
              } catch (err) {
                setError(cmsErrorMessage(err));
              } finally {
                setUploading(false);
                e.target.value = "";
              }
            })();
          }}
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="banner-link">Link URL</Label>
        <Input
          id="banner-link"
          value={linkUrl}
          onChange={(e) => setLinkUrl(e.target.value)}
          placeholder="/products or https://…"
        />
      </div>
      <div className="grid gap-4 sm:grid-cols-2">
        <div className="space-y-2">
          <Label htmlFor="banner-starts">Starts at</Label>
          <Input
            id="banner-starts"
            type="datetime-local"
            value={startsAt.slice(0, 16)}
            onChange={(e) => setStartsAt(e.target.value)}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="banner-ends">Ends at</Label>
          <Input
            id="banner-ends"
            type="datetime-local"
            value={endsAt.slice(0, 16)}
            onChange={(e) => setEndsAt(e.target.value)}
          />
        </div>
      </div>
      <div className="space-y-2">
        <Label htmlFor="banner-sort">Sort</Label>
        <Input
          id="banner-sort"
          type="number"
          min={0}
          value={sort}
          onChange={(e) => setSort(e.target.value)}
        />
      </div>
      <div className="space-y-2">
        <Label>Status</Label>
        <Select
          value={status}
          onValueChange={(v) => setStatus(v as CmsBannerStatus)}
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
        <Button type="submit" disabled={save.isPending || uploading}>
          {save.isPending ? "Saving…" : "Save"}
        </Button>
        <Button type="button" variant="outline" onClick={() => onDone?.()}>
          Cancel
        </Button>
      </div>
    </form>
  );
}
