"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

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
import { FilterBar } from "@/components/admin/layout/filter-bar";
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
import { Textarea } from "@/components/ui/textarea";
import { useAdminMe } from "@/hooks/use-admin-me";
import { canManageCatalog } from "@/lib/admin/can-manage-catalog";
import {
  createCategory,
  deleteCategory,
  getCategory,
  listCategories,
  updateCategory,
} from "@/lib/api/catalog/categories";
import { catalogErrorMessage } from "@/lib/api/catalog/errors";
import type { CatalogStatus, Category } from "@/lib/api/catalog/types";

export function CategoriesListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [qApplied, setQApplied] = useState("");
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ["admin", "catalog", "categories", page, qApplied],
    queryFn: () => listCategories({ page, per_page: 20, q: qApplied || undefined }),
  });
  const parents = useQuery({
    queryKey: ["admin", "catalog", "categories", "parents"],
    queryFn: () => listCategories({ per_page: 100 }),
  });
  const editQuery = useQuery({
    queryKey: ["admin", "catalog", "categories", editId],
    queryFn: () => getCategory(editId!),
    enabled: editId != null,
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;
  const parentOptions = parents.data?.data ?? [];

  return (
    <div className="space-y-6">
      <PageHeader
        title="Category Management"
        description="Hierarchy and visibility for the merchandising tree."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              + Add Category
            </Button>
          ) : null
        }
      />

      <DataTableShell>
        <FilterBar>
          <form
            className="flex gap-2"
            onSubmit={(e) => {
              e.preventDefault();
              setPage(1);
              setQApplied(q.trim());
            }}
          >
            <Input
              placeholder="Search by name…"
              value={q}
              onChange={(e) => setQ(e.target.value)}
            />
            <Button type="submit" variant="outline">
              Search
            </Button>
          </form>
        </FilterBar>

        {query.isPending ? (
          <LoadingState />
        ) : query.isError ? (
          <p className="px-4 py-6 text-sm text-[#ba1a1a]">
            {catalogErrorMessage(query.error)}
          </p>
        ) : rows.length === 0 ? (
          <EmptyState title="No categories yet" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>Parent</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((cat) => (
                  <TableRow key={cat.id}>
                    <TableCell className="font-medium">{cat.name}</TableCell>
                    <TableCell>{cat.parent_id ?? "—"}</TableCell>
                    <TableCell>
                      <StatusBadge status={cat.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() => setEditId(cat.id)}
                          >
                            Edit
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete Category"
                            onConfirm={async () => {
                              await deleteCategory(cat.id);
                              await queryClient.invalidateQueries({
                                queryKey: ["admin", "catalog", "categories"],
                              });
                            }}
                          />
                        </div>
                      ) : (
                        <Button asChild variant="outline" size="sm">
                          <Link href={`/admin/catalog/categories/${cat.id}`}>
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
        title="Add New Category"
        description="Create a merchandising node in the catalog tree."
      >
        <CategoryFormFields
          mode="create"
          embedded
          parentOptions={parentOptions}
          initial={{
            name: "",
            slug: "",
            parent_id: null,
            status: "active",
            description: null,
          }}
          onDone={() => setCreateOpen(false)}
        />
      </ModalForm>

      <ModalForm
        open={editId !== null}
        onOpenChange={(open) => {
          if (!open) setEditId(null);
        }}
        title="Edit Category"
      >
        {editQuery.isPending ? (
          <p className="text-sm text-[#71717A]">Loading…</p>
        ) : editQuery.data?.data ? (
          <CategoryFormFields
            key={editQuery.data.data.id}
            mode="edit"
            embedded
            categoryId={editQuery.data.data.id}
            parentOptions={parentOptions.filter(
              (c) => c.id !== editQuery.data?.data.id,
            )}
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

function CategoryFormFields({
  mode,
  categoryId,
  initial,
  parentOptions,
  embedded = false,
  onDone,
}: {
  mode: "create" | "edit";
  categoryId?: number;
  initial: {
    name: string;
    slug: string;
    parent_id: number | null;
    status: CatalogStatus;
    description?: string | null;
  };
  parentOptions: Category[];
  embedded?: boolean;
  onDone?: () => void;
}) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [name, setName] = useState(initial.name);
  const [slug, setSlug] = useState(initial.slug ?? "");
  const [parentId, setParentId] = useState(
    initial.parent_id ? String(initial.parent_id) : "none",
  );
  const [status, setStatus] = useState<CatalogStatus>(initial.status);
  const [description, setDescription] = useState(initial.description ?? "");
  const [error, setError] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: async () => {
      const body = {
        name,
        slug: slug || null,
        parent_id: parentId === "none" ? null : Number(parentId),
        status,
        description: description || null,
      };
      if (mode === "create") return createCategory(body);
      return updateCategory(categoryId!, body);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "catalog", "categories"],
      });
      if (onDone) {
        onDone();
        return;
      }
      router.replace("/admin/catalog/categories");
    },
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  const form = (
        <form
          className="space-y-4"
          onSubmit={(e: FormEvent) => {
            e.preventDefault();
            setError(null);
            void save.mutateAsync();
          }}
        >
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
            <Label htmlFor="slug">Slug</Label>
            <Input
              id="slug"
              value={slug}
              onChange={(e) => setSlug(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label>Parent</Label>
            <Select value={parentId} onValueChange={setParentId}>
              <SelectTrigger>
                <SelectValue placeholder="Không có" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Không có</SelectItem>
                {parentOptions.map((c) => (
                  <SelectItem key={c.id} value={String(c.id)}>
                    {c.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
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
          <div className="space-y-2">
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
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
                <Link href="/admin/catalog/categories">Cancel</Link>
              </Button>
            )}
            {!embedded && mode === "edit" && categoryId ? (
              <ConfirmDeleteButton
                onConfirm={async () => {
                  try {
                    await deleteCategory(categoryId);
                    await queryClient.invalidateQueries({
                      queryKey: ["admin", "catalog", "categories"],
                    });
                    router.replace("/admin/catalog/categories");
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
        <CardTitle>
          {mode === "create" ? "Add category" : "Edit category"}
        </CardTitle>
      </CardHeader>
      <CardContent>{form}</CardContent>
    </Card>
  );
}

function CategoryForm({
  mode,
  categoryId,
}: {
  mode: "create" | "edit";
  categoryId?: number;
}) {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;

  const existing = useQuery({
    queryKey: ["admin", "catalog", "categories", categoryId],
    queryFn: () => getCategory(categoryId!),
    enabled: mode === "edit" && !!categoryId,
  });

  const parents = useQuery({
    queryKey: ["admin", "catalog", "categories", "parents"],
    queryFn: () => listCategories({ per_page: 100 }),
  });

  const parentOptions = (parents.data?.data ?? []).filter(
    (c) => c.id !== categoryId,
  );

  if (mode === "create") {
    return (
      <RequireCatalogManage redirectTo="/admin/catalog/categories">
        <CategoryFormFields
          mode="create"
          parentOptions={parentOptions}
          initial={{
            name: "",
            slug: "",
            parent_id: null,
            status: "active",
            description: null,
          }}
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

  const category = existing.data.data;
  if (!manage) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{category.name}</CardTitle>
          <CardDescription>Chỉ xem (staff).</CardDescription>
        </CardHeader>
        <CardContent>
          <Button asChild variant="outline">
            <Link href="/admin/catalog/categories">Quay lại</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <CategoryFormFields
      key={category.id}
      mode="edit"
      categoryId={categoryId}
      parentOptions={parentOptions}
      initial={category}
    />
  );
}

export function CategoryCreatePage() {
  return <CategoryForm mode="create" />;
}

export function CategoryEditPage({ categoryId }: { categoryId: number }) {
  return <CategoryForm mode="edit" categoryId={categoryId} />;
}
