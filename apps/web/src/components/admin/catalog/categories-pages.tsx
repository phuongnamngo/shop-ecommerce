"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { ConfirmDeleteButton } from "@/components/admin/catalog/confirm-delete-button";
import { RequireCatalogManage } from "@/components/admin/require-catalog-manage";
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
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [qApplied, setQApplied] = useState("");

  const query = useQuery({
    queryKey: ["admin", "catalog", "categories", page, qApplied],
    queryFn: () => listCategories({ page, per_page: 20, q: qApplied || undefined }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Categories</h1>
          <p className="text-sm text-muted-foreground">Quản lý danh mục.</p>
        </div>
        {manage ? (
          <Button asChild>
            <Link href="/admin/catalog/categories/new">Thêm category</Link>
          </Button>
        ) : null}
      </div>

      <Card>
        <CardContent className="space-y-4 pt-6">
          <form
            className="flex gap-2"
            onSubmit={(e) => {
              e.preventDefault();
              setPage(1);
              setQApplied(q.trim());
            }}
          >
            <Input
              placeholder="Tìm theo tên…"
              value={q}
              onChange={(e) => setQ(e.target.value)}
            />
            <Button type="submit" variant="outline">
              Tìm
            </Button>
          </form>

          {query.isPending ? (
            <p className="text-sm text-muted-foreground">Đang tải…</p>
          ) : query.isError ? (
            <p className="text-sm text-destructive">
              {catalogErrorMessage(query.error)}
            </p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Parent</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((cat) => (
                    <TableRow key={cat.id}>
                      <TableCell>{cat.id}</TableCell>
                      <TableCell>{cat.name}</TableCell>
                      <TableCell>{cat.parent_id ?? "—"}</TableCell>
                      <TableCell>{cat.status}</TableCell>
                      <TableCell className="text-right">
                        <Button asChild variant="outline" size="sm">
                          <Link href={`/admin/catalog/categories/${cat.id}`}>
                            {manage ? "Sửa" : "Xem"}
                          </Link>
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

function CategoryFormFields({
  mode,
  categoryId,
  initial,
  parentOptions,
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
    onSuccess: async (res) => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "catalog", "categories"],
      });
      router.replace(`/admin/catalog/categories/${res.data.id}`);
    },
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>
          {mode === "create" ? "Thêm category" : "Sửa category"}
        </CardTitle>
      </CardHeader>
      <CardContent>
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
              {save.isPending ? "Đang lưu…" : "Lưu"}
            </Button>
            <Button asChild type="button" variant="outline">
              <Link href="/admin/catalog/categories">Hủy</Link>
            </Button>
            {mode === "edit" && categoryId ? (
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
      </CardContent>
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
