"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";

import { ConfirmDeleteButton } from "@/components/admin/catalog/confirm-delete-button";
import { RequireCatalogManage } from "@/components/admin/require-catalog-manage";
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
import { canManageCatalog } from "@/lib/admin/can-manage-catalog";
import type { Brand, CatalogStatus } from "@/lib/api/catalog/types";
import {
  createBrand,
  deleteBrand,
  getBrand,
  listBrands,
  updateBrand,
} from "@/lib/api/catalog/brands";
import { catalogErrorMessage } from "@/lib/api/catalog/errors";

export function BrandsListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);
  const query = useQuery({
    queryKey: ["admin", "catalog", "brands", page],
    queryFn: () => listBrands({ page, per_page: 20 }),
  });
  const editQuery = useQuery({
    queryKey: ["admin", "catalog", "brands", editId],
    queryFn: () => getBrand(editId!),
    enabled: editId != null,
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Brand Management"
        description="Maison profiles used across the catalog."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              + Add Brand
            </Button>
          ) : null
        }
      />

      <DataTableShell>
        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {catalogErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No brands yet" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Slug</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((brand) => (
                  <TableRow key={brand.id}>
                    <TableCell className="font-medium">{brand.name}</TableCell>
                    <TableCell className="font-mono text-xs">{brand.slug}</TableCell>
                    <TableCell>
                      <StatusBadge status={brand.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditId(brand.id)}
                          >
                            Edit
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete brand"
                            onConfirm={async () => {
                              await deleteBrand(brand.id);
                              await queryClient.invalidateQueries({
                                queryKey: ["admin", "catalog", "brands"],
                              });
                            }}
                          />
                        </div>
                      ) : (
                        <Button asChild variant="ghost" size="sm">
                          <Link href={`/admin/catalog/brands/${brand.id}`}>
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
        title="Add Brand"
        description="Create a designer or maison profile."
      >
        <BrandFormFields
          mode="create"
          embedded
          initial={{ name: "", slug: "", status: "active" }}
          onDone={() => setCreateOpen(false)}
        />
      </ModalForm>
      <ModalForm
        open={editId !== null}
        onOpenChange={(open) => {
          if (!open) setEditId(null);
        }}
        title="Edit Brand Profile"
      >
        {editQuery.isPending ? (
          <p className="text-sm text-[#71717A]">Loading…</p>
        ) : editQuery.data?.data ? (
          <BrandFormFields
            key={editQuery.data.data.id}
            mode="edit"
            embedded
            brandId={editQuery.data.data.id}
            initial={editQuery.data.data}
            onDone={() => setEditId(null)}
          />
        ) : (
          <p className="text-sm text-[#ba1a1a]">
            {catalogErrorMessage(editQuery.error)}
          </p>
        )}
      </ModalForm>
    </div>
  );
}

function BrandFormFields({
  mode,
  brandId,
  initial,
  embedded = false,
  onDone,
}: {
  mode: "create" | "edit";
  brandId?: number;
  initial: Pick<Brand, "name" | "slug" | "status">;
  embedded?: boolean;
  onDone?: () => void;
}) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [name, setName] = useState(initial.name);
  const [slug, setSlug] = useState(initial.slug ?? "");
  const [status, setStatus] = useState<CatalogStatus>(initial.status);
  const [error, setError] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: async () => {
      const body = {
        name,
        slug: slug || null,
        status,
      };
      if (mode === "create") {
        return createBrand(body);
      }
      return updateBrand(brandId!, body);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "catalog", "brands"],
      });
      if (onDone) {
        onDone();
        return;
      }
      router.replace("/admin/catalog/brands");
    },
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    await save.mutateAsync();
  }

  const form = (
        <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
          <div className="space-y-2">
            <Label htmlFor="name">Name</Label>
            <Input
              id="name"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="slug">Slug (optional)</Label>
            <Input
              id="slug"
              value={slug}
              onChange={(e) => setSlug(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label>Status</Label>
            <Select
              value={status}
              onValueChange={(v) => setStatus(v as CatalogStatus)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="draft">draft</SelectItem>
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
            ) : (
              <Button asChild type="button" variant="outline">
                <Link href="/admin/catalog/brands">Cancel</Link>
              </Button>
            )}
            {!embedded && mode === "edit" && brandId ? (
              <ConfirmDeleteButton
                onConfirm={async () => {
                  try {
                    await deleteBrand(brandId);
                    await queryClient.invalidateQueries({
                      queryKey: ["admin", "catalog", "brands"],
                    });
                    router.replace("/admin/catalog/brands");
                  } catch (err) {
                    throw new Error(catalogErrorMessage(err));
                  }
                }}
              />
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
        <CardTitle>{mode === "create" ? "Add brand" : "Edit brand"}</CardTitle>
      </CardHeader>
      <CardContent>{form}</CardContent>
    </Card>
  );
}

function BrandForm({
  mode,
  brandId,
}: {
  mode: "create" | "edit";
  brandId?: number;
}) {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;

  const existing = useQuery({
    queryKey: ["admin", "catalog", "brands", brandId],
    queryFn: () => getBrand(brandId!),
    enabled: mode === "edit" && !!brandId,
  });

  if (mode === "create") {
    return (
      <RequireCatalogManage redirectTo="/admin/catalog/brands">
        <BrandFormFields
          mode="create"
          initial={{ name: "", slug: "", status: "active" }}
        />
      </RequireCatalogManage>
    );
  }

  if (existing.isPending) {
    return <p className="text-sm text-muted-foreground">Đang tải…</p>;
  }
  if (existing.isError || !existing.data?.data) {
    return (
      <p className="text-sm text-destructive">
        {catalogErrorMessage(existing.error)}
      </p>
    );
  }

  const brand = existing.data.data;
  if (!manage) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{brand.name}</CardTitle>
          <CardDescription>Chỉ xem (staff).</CardDescription>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p>Slug: {brand.slug}</p>
          <p>Status: {brand.status}</p>
          <Button asChild variant="outline">
            <Link href="/admin/catalog/brands">Quay lại</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <BrandFormFields
      key={brand.id}
      mode="edit"
      brandId={brandId}
      initial={brand}
    />
  );
}

export function BrandCreatePage() {
  return <BrandForm mode="create" />;
}

export function BrandEditPage({ brandId }: { brandId: number }) {
  return <BrandForm mode="edit" brandId={brandId} />;
}
