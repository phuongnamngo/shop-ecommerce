"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";

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
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const query = useQuery({
    queryKey: ["admin", "catalog", "brands", page],
    queryFn: () => listBrands({ page, per_page: 20 }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Brands</h1>
          <p className="text-sm text-muted-foreground">Quản lý thương hiệu.</p>
        </div>
        {manage ? (
          <Button asChild>
            <Link href="/admin/catalog/brands/new">Thêm brand</Link>
          </Button>
        ) : null}
      </div>

      <Card>
        <CardContent className="pt-6">
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
                    <TableHead>Slug</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {rows.map((brand) => (
                    <TableRow key={brand.id}>
                      <TableCell>{brand.id}</TableCell>
                      <TableCell>{brand.name}</TableCell>
                      <TableCell>{brand.slug}</TableCell>
                      <TableCell>{brand.status}</TableCell>
                      <TableCell className="space-x-2 text-right">
                        {manage ? (
                          <Button asChild variant="outline" size="sm">
                            <Link href={`/admin/catalog/brands/${brand.id}`}>
                              Sửa
                            </Link>
                          </Button>
                        ) : (
                          <Button asChild variant="ghost" size="sm">
                            <Link href={`/admin/catalog/brands/${brand.id}`}>
                              Xem
                            </Link>
                          </Button>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
              {meta ? (
                <div className="mt-4 flex items-center justify-between text-sm">
                  <span>
                    Trang {meta.current_page}/{meta.last_page} · {meta.total}{" "}
                    brands
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

function BrandFormFields({
  mode,
  brandId,
  initial,
}: {
  mode: "create" | "edit";
  brandId?: number;
  initial: Pick<Brand, "name" | "slug" | "status">;
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
    onSuccess: async (res) => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "catalog", "brands"],
      });
      router.replace(`/admin/catalog/brands/${res.data.id}`);
    },
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    await save.mutateAsync();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{mode === "create" ? "Thêm brand" : "Sửa brand"}</CardTitle>
      </CardHeader>
      <CardContent>
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
              {save.isPending ? "Đang lưu…" : "Lưu"}
            </Button>
            <Button asChild type="button" variant="outline">
              <Link href="/admin/catalog/brands">Hủy</Link>
            </Button>
            {mode === "edit" && brandId ? (
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
      </CardContent>
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
