"use client";

import { FormEvent, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Pencil, Trash2 } from "lucide-react";

import { ConfirmDeleteButton } from "@/components/admin/catalog/confirm-delete-button";
import { ProductFormDialog } from "@/components/admin/catalog/product-form-dialog";
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
import { listBrands } from "@/lib/api/catalog/brands";
import { listCategories } from "@/lib/api/catalog/categories";
import { catalogErrorMessage } from "@/lib/api/catalog/errors";
import {
  attachProductImage,
  createProduct,
  createVariant,
  deleteProduct,
  deleteProductImage,
  deleteVariant,
  getProduct,
  listProducts,
  updateProduct,
  updateVariant,
  uploadCatalogImage,
  type CreateVariantInput,
} from "@/lib/api/catalog/products";
import type {
  CatalogStatus,
  Product,
} from "@/lib/api/catalog/types";

type VariantDraft = {
  key: string;
  sku: string;
  price: string;
  is_default: boolean;
};

function newVariantDraft(isDefault = false): VariantDraft {
  return {
    key: crypto.randomUUID(),
    sku: "",
    price: "0",
    is_default: isDefault,
  };
}

function countDefaults(variants: VariantDraft[]): number {
  return variants.filter((v) => v.is_default).length;
}

export function ProductsListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [q, setQ] = useState("");
  const [qApplied, setQApplied] = useState("");
  const [status, setStatus] = useState<string>("all");
  const [brandId, setBrandId] = useState("all");
  const [categoryId, setCategoryId] = useState("all");
  const [createOpen, setCreateOpen] = useState(false);
  const [editId, setEditId] = useState<number | null>(null);

  const brands = useQuery({
    queryKey: ["admin", "catalog", "brands", "filter"],
    queryFn: () => listBrands({ per_page: 100 }),
  });
  const categories = useQuery({
    queryKey: ["admin", "catalog", "categories", "filter"],
    queryFn: () => listCategories({ per_page: 100 }),
  });

  const query = useQuery({
    queryKey: [
      "admin",
      "catalog",
      "products",
      page,
      qApplied,
      status,
      brandId,
      categoryId,
    ],
    queryFn: () =>
      listProducts({
        page,
        per_page: 20,
        q: qApplied || undefined,
        status: status === "all" ? undefined : status,
        brand_id: brandId === "all" ? undefined : Number(brandId),
        category_id:
          categoryId === "all" ? undefined : Number(categoryId),
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Product Management"
        description="Catalog items, SKUs, and publication status."
        actions={
          manage ? (
            <Button type="button" onClick={() => setCreateOpen(true)}>
              + Add Product
            </Button>
          ) : null
        }
      />

      <DataTableShell>
        <FilterBar>
          <form
            className="flex flex-wrap gap-2"
            onSubmit={(e) => {
              e.preventDefault();
              setPage(1);
              setQApplied(q.trim());
            }}
          >
            <Input
              className="h-9 max-w-xs"
              placeholder="Search by product name, SKU..."
              value={q}
              onChange={(e) => setQ(e.target.value)}
            />
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger className="w-36">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">all</SelectItem>
                <SelectItem value="draft">draft</SelectItem>
                <SelectItem value="active">active</SelectItem>
                <SelectItem value="inactive">inactive</SelectItem>
              </SelectContent>
            </Select>
            <Select value={brandId} onValueChange={setBrandId}>
              <SelectTrigger className="w-40">
                <SelectValue placeholder="Brand" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Brand: all</SelectItem>
                {(brands.data?.data ?? []).map((b) => (
                  <SelectItem key={b.id} value={String(b.id)}>
                    {b.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Select value={categoryId} onValueChange={setCategoryId}>
              <SelectTrigger className="w-44">
                <SelectValue placeholder="Category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Category: all</SelectItem>
                {(categories.data?.data ?? []).map((c) => (
                  <SelectItem key={c.id} value={String(c.id)}>
                    {c.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Button type="submit" variant="outline">
              Filter
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
          <EmptyState title="No products match this filter" />
        ) : (
          <>
            <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>Brand</TableHead>
                  <TableHead>Categories</TableHead>
                  <TableHead className="text-right">Price</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((p) => {
                  const sku =
                    p.variants.find((v) => v.is_default)?.sku ??
                    p.variants[0]?.sku;
                  const price =
                    p.variants.find((v) => v.is_default)?.price ??
                    p.variants[0]?.price;
                  const thumb =
                    p.images.find((img) => img.is_primary)?.thumbnail_url ??
                    p.images[0]?.thumbnail_url ??
                    p.images[0]?.url;
                  return (
                    <TableRow key={p.id}>
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <div className="flex size-11 overflow-hidden rounded-lg bg-[#f8fafc]">
                            {thumb ? (
                              // eslint-disable-next-line @next/next/no-img-element
                              <img
                                src={thumb}
                                alt=""
                                className="size-11 object-cover transition-transform duration-200 hover:scale-110"
                              />
                            ) : (
                              <span className="m-auto text-[10px] font-bold text-[#64748b]">
                                SKU
                              </span>
                            )}
                          </div>
                          <div>
                            <div className="font-medium text-[#0f172a]">{p.name}</div>
                            <div className="font-mono text-[11px] text-[#64748b]">
                              SKU: {sku ?? "—"}
                            </div>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>{p.brand?.name ?? "—"}</TableCell>
                      <TableCell className="max-w-[180px] truncate text-[#64748b]">
                        {p.categories.map((c) => c.name).join(", ") || "—"}
                      </TableCell>
                      <TableCell className="text-right font-semibold tabular-nums">
                        {price ?? "—"}
                      </TableCell>
                      <TableCell>
                        <StatusBadge status={p.status} />
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-1">
                          {manage ? (
                            <>
                              <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="size-8"
                                onClick={() => setEditId(p.id)}
                                aria-label="Edit product"
                              >
                                <Pencil className="size-4" />
                              </Button>
                              <ConfirmDeleteButton
                                title="Confirm Catalog Item Deletion"
                                description="This removes the product from catalog channels. Soft-delete is applied by the API."
                                onConfirm={async () => {
                                  await deleteProduct(p.id);
                                  await queryClient.invalidateQueries({
                                    queryKey: ["admin", "catalog", "products"],
                                  });
                                }}
                                trigger={
                                  <span className="inline-flex size-8 items-center justify-center rounded-md text-[#fa896b] hover:bg-[#fdede8]">
                                    <Trash2 className="size-4" />
                                  </span>
                                }
                              />
                              <Button asChild variant="ghost" size="sm">
                                <Link href={`/admin/catalog/products/${p.id}`}>
                                  Full editor
                                </Link>
                              </Button>
                            </>
                          ) : (
                            <Button asChild variant="outline" size="sm">
                              <Link href={`/admin/catalog/products/${p.id}`}>
                                View
                              </Link>
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  );
                })}
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

      <ProductFormDialog open={createOpen} onOpenChange={setCreateOpen} />
      <ProductFormDialog
        open={editId !== null}
        onOpenChange={(open) => {
          if (!open) setEditId(null);
        }}
        productId={editId ?? undefined}
      />
    </div>
  );
}

export function ProductCreatePage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const brands = useQuery({
    queryKey: ["admin", "catalog", "brands", "all"],
    queryFn: () => listBrands({ per_page: 100 }),
  });
  const categories = useQuery({
    queryKey: ["admin", "catalog", "categories", "all"],
    queryFn: () => listCategories({ per_page: 100 }),
  });

  const [name, setName] = useState("");
  const [slug, setSlug] = useState("");
  const [brandId, setBrandId] = useState("none");
  const [status, setStatus] = useState<CatalogStatus>("draft");
  const [description, setDescription] = useState("");
  const [categoryIds, setCategoryIds] = useState<number[]>([]);
  const [variants, setVariants] = useState<VariantDraft[]>([
    newVariantDraft(true),
  ]);
  const [error, setError] = useState<string | null>(null);

  const defaultsOk = countDefaults(variants) === 1 && variants.length >= 1;

  const save = useMutation({
    mutationFn: async () => {
      if (!defaultsOk) {
        throw new Error("Cần ≥1 variant và đúng một is_default.");
      }
      const payloadVariants: CreateVariantInput[] = variants.map((v) => ({
        sku: v.sku,
        price: Number(v.price),
        is_default: v.is_default,
      }));
      return createProduct({
        name,
        slug: slug || null,
        brand_id: brandId === "none" ? null : Number(brandId),
        status,
        description: description || null,
        category_ids: categoryIds,
        variants: payloadVariants,
      });
    },
    onSuccess: async (res) => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "catalog", "products"],
      });
      router.replace(`/admin/catalog/products/${res.data.id}`);
    },
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  return (
    <RequireCatalogManage redirectTo="/admin/catalog/products">
      <Card>
        <CardHeader>
          <CardTitle>Thêm product</CardTitle>
          <CardDescription>
            API bắt buộc variants lúc create (đúng 1 default).
          </CardDescription>
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
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Brand</Label>
                <Select value={brandId} onValueChange={setBrandId}>
                  <SelectTrigger>
                    <SelectValue placeholder="Brand" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Không có</SelectItem>
                    {(brands.data?.data ?? []).map((b) => (
                      <SelectItem key={b.id} value={String(b.id)}>
                        {b.name}
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
            </div>
            <div className="space-y-2">
              <Label>Categories</Label>
              <div className="flex max-h-40 flex-wrap gap-2 overflow-y-auto rounded-md border p-2">
                {(categories.data?.data ?? []).map((c) => {
                  const checked = categoryIds.includes(c.id);
                  return (
                    <label
                      key={c.id}
                      className="flex items-center gap-1 text-sm"
                    >
                      <input
                        type="checkbox"
                        checked={checked}
                        onChange={() =>
                          setCategoryIds((prev) =>
                            checked
                              ? prev.filter((id) => id !== c.id)
                              : [...prev, c.id],
                          )
                        }
                      />
                      {c.name}
                    </label>
                  );
                })}
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <Label>Variants</Label>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    setVariants((prev) => [...prev, newVariantDraft(false)])
                  }
                >
                  Thêm variant
                </Button>
              </div>
              <div className="space-y-2">
                {variants.map((v) => (
                  <div
                    key={v.key}
                    className="grid gap-2 rounded-md border p-3 sm:grid-cols-4"
                  >
                    <Input
                      placeholder="SKU"
                      required
                      value={v.sku}
                      onChange={(e) =>
                        setVariants((prev) =>
                          prev.map((row) =>
                            row.key === v.key
                              ? { ...row, sku: e.target.value }
                              : row,
                          ),
                        )
                      }
                    />
                    <Input
                      placeholder="Price"
                      required
                      type="number"
                      min={0}
                      step="0.01"
                      value={v.price}
                      onChange={(e) =>
                        setVariants((prev) =>
                          prev.map((row) =>
                            row.key === v.key
                              ? { ...row, price: e.target.value }
                              : row,
                          ),
                        )
                      }
                    />
                    <label className="flex items-center gap-2 text-sm">
                      <input
                        type="radio"
                        name="default-variant"
                        checked={v.is_default}
                        onChange={() =>
                          setVariants((prev) =>
                            prev.map((row) => ({
                              ...row,
                              is_default: row.key === v.key,
                            })),
                          )
                        }
                      />
                      Default
                    </label>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      disabled={variants.length <= 1}
                      onClick={() =>
                        setVariants((prev) =>
                          prev.filter((row) => row.key !== v.key),
                        )
                      }
                    >
                      Xóa dòng
                    </Button>
                  </div>
                ))}
              </div>
              {!defaultsOk ? (
                <p className="text-sm text-destructive">
                  Cần đúng một variant mặc định.
                </p>
              ) : null}
            </div>

            {error ? (
              <p className="text-sm text-destructive" role="alert">
                {error}
              </p>
            ) : null}
            <div className="flex gap-2">
              <Button
                type="submit"
                disabled={save.isPending || !defaultsOk}
              >
                {save.isPending ? "Đang tạo…" : "Tạo product"}
              </Button>
              <Button asChild type="button" variant="outline">
                <Link href="/admin/catalog/products">Hủy</Link>
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </RequireCatalogManage>
  );
}

export function ProductEditPage({ productId }: { productId: number }) {
  const productQuery = useQuery({
    queryKey: ["admin", "catalog", "products", productId],
    queryFn: () => getProduct(productId),
  });

  if (productQuery.isPending) {
    return <p className="text-sm text-muted-foreground">Đang tải…</p>;
  }
  if (productQuery.isError || !productQuery.data?.data) {
    return (
      <p className="text-sm text-destructive">
        {catalogErrorMessage(productQuery.error)}
      </p>
    );
  }

  const product = productQuery.data.data;
  return (
    <ProductEditView
      key={product.id}
      productId={productId}
      initial={product}
    />
  );
}

function ProductEditView({
  productId,
  initial,
}: {
  productId: number;
  initial: Product;
}) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManageCatalog(me.data.roles) : false;

  const productQuery = useQuery({
    queryKey: ["admin", "catalog", "products", productId],
    queryFn: () => getProduct(productId),
    initialData: { data: initial } as Awaited<ReturnType<typeof getProduct>>,
  });
  const brands = useQuery({
    queryKey: ["admin", "catalog", "brands", "all"],
    queryFn: () => listBrands({ per_page: 100 }),
  });
  const categories = useQuery({
    queryKey: ["admin", "catalog", "categories", "all"],
    queryFn: () => listCategories({ per_page: 100 }),
  });

  const product = productQuery.data?.data ?? initial;
  const [name, setName] = useState(initial.name);
  const [slug, setSlug] = useState(initial.slug ?? "");
  const [brandId, setBrandId] = useState(
    initial.brand ? String(initial.brand.id) : "none",
  );
  const [status, setStatus] = useState<CatalogStatus>(initial.status);
  const [description, setDescription] = useState(initial.description ?? "");
  const [categoryIds, setCategoryIds] = useState(
    initial.categories.map((c) => c.id),
  );
  const [error, setError] = useState<string | null>(null);
  const [variantSku, setVariantSku] = useState("");
  const [variantPrice, setVariantPrice] = useState("0");

  const invalidate = async () => {
    await queryClient.invalidateQueries({
      queryKey: ["admin", "catalog", "products", productId],
    });
    await queryClient.invalidateQueries({
      queryKey: ["admin", "catalog", "products"],
    });
  };

  const saveFields = useMutation({
    mutationFn: () =>
      updateProduct(productId, {
        name,
        slug: slug || null,
        brand_id: brandId === "none" ? null : Number(brandId),
        status,
        description: description || null,
        category_ids: categoryIds,
      }),
    onSuccess: () => void invalidate(),
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  const images = product.images ?? [];

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-semibold">{product.name}</h1>
        <Button asChild variant="outline">
          <Link href="/admin/catalog/products">Danh sách</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Thông tin product</CardTitle>
          {!manage ? (
            <CardDescription>Chỉ xem (staff).</CardDescription>
          ) : null}
        </CardHeader>
        <CardContent>
          <form
            className="space-y-4"
            onSubmit={(e) => {
              e.preventDefault();
              if (!manage) return;
              setError(null);
              void saveFields.mutateAsync();
            }}
          >
            <div className="space-y-2">
              <Label>Name</Label>
              <Input
                value={name}
                disabled={!manage}
                onChange={(e) => setName(e.target.value)}
                required
              />
            </div>
            <div className="space-y-2">
              <Label>Slug</Label>
              <Input
                value={slug}
                disabled={!manage}
                onChange={(e) => setSlug(e.target.value)}
              />
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Brand</Label>
                <Select
                  value={brandId}
                  onValueChange={setBrandId}
                  disabled={!manage}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Không có</SelectItem>
                    {(brands.data?.data ?? []).map((b) => (
                      <SelectItem key={b.id} value={String(b.id)}>
                        {b.name}
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
                  disabled={!manage}
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
            </div>
            <div className="space-y-2">
              <Label>Categories</Label>
              <div className="flex max-h-32 flex-wrap gap-2 overflow-y-auto rounded-md border p-2">
                {(categories.data?.data ?? []).map((c) => {
                  const checked = categoryIds.includes(c.id);
                  return (
                    <label
                      key={c.id}
                      className="flex items-center gap-1 text-sm"
                    >
                      <input
                        type="checkbox"
                        disabled={!manage}
                        checked={checked}
                        onChange={() =>
                          setCategoryIds((prev) =>
                            checked
                              ? prev.filter((id) => id !== c.id)
                              : [...prev, c.id],
                          )
                        }
                      />
                      {c.name}
                    </label>
                  );
                })}
              </div>
            </div>
            <div className="space-y-2">
              <Label>Description</Label>
              <Textarea
                value={description}
                disabled={!manage}
                onChange={(e) => setDescription(e.target.value)}
              />
            </div>
            {error ? (
              <p className="text-sm text-destructive" role="alert">
                {error}
              </p>
            ) : null}
            {manage ? (
              <div className="flex flex-wrap gap-2">
                <Button type="submit" disabled={saveFields.isPending}>
                  {saveFields.isPending ? "Đang lưu…" : "Lưu fields"}
                </Button>
                <ConfirmDeleteButton
                  onConfirm={async () => {
                    try {
                      await deleteProduct(productId);
                      await queryClient.invalidateQueries({
                        queryKey: ["admin", "catalog", "products"],
                      });
                      router.replace("/admin/catalog/products");
                    } catch (err) {
                      throw new Error(catalogErrorMessage(err));
                    }
                  }}
                />
              </div>
            ) : null}
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Variants</CardTitle>
          <CardDescription>
            Sửa qua endpoint riêng (không nested trong PATCH product).
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>SKU</TableHead>
                <TableHead>Price</TableHead>
                <TableHead>Default</TableHead>
                {manage ? <TableHead /> : null}
              </TableRow>
            </TableHeader>
            <TableBody>
              {product.variants.map((v) => (
                <TableRow key={v.id}>
                  <TableCell>{v.sku}</TableCell>
                  <TableCell>{v.price}</TableCell>
                  <TableCell>{v.is_default ? "yes" : "no"}</TableCell>
                  {manage ? (
                    <TableCell className="space-x-2 text-right">
                      {!v.is_default ? (
                        <Button
                          type="button"
                          size="sm"
                          variant="outline"
                          onClick={() =>
                            void updateVariant(productId, v.id, {
                              is_default: true,
                            })
                              .then(invalidate)
                              .catch((err) =>
                                setError(catalogErrorMessage(err)),
                              )
                          }
                        >
                          Set default
                        </Button>
                      ) : null}
                      <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={() =>
                          void deleteVariant(productId, v.id)
                            .then(invalidate)
                            .catch((err) =>
                              setError(catalogErrorMessage(err)),
                            )
                        }
                      >
                        Xóa
                      </Button>
                    </TableCell>
                  ) : null}
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {manage ? (
            <div className="flex flex-wrap gap-2">
              <Input
                className="max-w-[140px]"
                placeholder="SKU"
                value={variantSku}
                onChange={(e) => setVariantSku(e.target.value)}
              />
              <Input
                className="max-w-[120px]"
                type="number"
                min={0}
                step="0.01"
                placeholder="Price"
                value={variantPrice}
                onChange={(e) => setVariantPrice(e.target.value)}
              />
              <Button
                type="button"
                onClick={() =>
                  void createVariant(productId, {
                    sku: variantSku,
                    price: Number(variantPrice),
                    is_default: product.variants.length === 0,
                  })
                    .then(() => {
                      setVariantSku("");
                      setVariantPrice("0");
                      return invalidate();
                    })
                    .catch((err) => setError(catalogErrorMessage(err)))
                }
              >
                Thêm variant
              </Button>
            </div>
          ) : null}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Images</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 sm:grid-cols-3">
            {images.map((img) => (
              <div key={img.id} className="rounded-md border p-2">
                {/* eslint-disable-next-line @next/next/no-img-element */}
                <img
                  src={img.thumbnail_url || img.url || undefined}
                  alt={img.alt ?? ""}
                  className="mb-2 h-28 w-full object-cover"
                />
                <p className="truncate text-xs text-muted-foreground">
                  {img.path}
                </p>
                {manage ? (
                  <Button
                    type="button"
                    size="sm"
                    variant="ghost"
                    className="mt-1"
                    onClick={() =>
                      void deleteProductImage(productId, img.id)
                        .then(invalidate)
                        .catch((err) => setError(catalogErrorMessage(err)))
                    }
                  >
                    Xóa ảnh
                  </Button>
                ) : null}
              </div>
            ))}
          </div>
          {manage ? (
            <Input
              type="file"
              accept="image/jpeg,image/jpg,image/png,image/webp"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (!file) return;
                void (async () => {
                  try {
                    const uploaded = await uploadCatalogImage(file);
                    await attachProductImage(productId, {
                      path: uploaded.data.path,
                      is_primary: images.length === 0,
                    });
                    await invalidate();
                  } catch (err) {
                    setError(catalogErrorMessage(err));
                  } finally {
                    e.target.value = "";
                  }
                })();
              }}
            />
          ) : null}
        </CardContent>
      </Card>
    </div>
  );
}
