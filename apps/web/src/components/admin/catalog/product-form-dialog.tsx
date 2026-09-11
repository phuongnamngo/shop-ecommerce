"use client";

import { FormEvent, useEffect, useState } from "react";
import Link from "next/link";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { ModalForm } from "@/components/admin/layout/modal-form";
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
import { Textarea } from "@/components/ui/textarea";
import { listBrands } from "@/lib/api/catalog/brands";
import { listCategories } from "@/lib/api/catalog/categories";
import { catalogErrorMessage } from "@/lib/api/catalog/errors";
import {
  createProduct,
  getProduct,
  updateProduct,
} from "@/lib/api/catalog/products";
import type { CatalogStatus, Product } from "@/lib/api/catalog/types";

export function ProductFormDialog({
  open,
  onOpenChange,
  productId,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  productId?: number;
}) {
  const queryClient = useQueryClient();
  const isEdit = productId != null;
  const brands = useQuery({
    queryKey: ["admin", "catalog", "brands", "filter"],
    queryFn: () => listBrands({ per_page: 100 }),
    enabled: open,
  });
  const categories = useQuery({
    queryKey: ["admin", "catalog", "categories", "filter"],
    queryFn: () => listCategories({ per_page: 100 }),
    enabled: open,
  });
  const existing = useQuery({
    queryKey: ["admin", "catalog", "products", productId],
    queryFn: () => getProduct(productId!),
    enabled: open && isEdit,
  });

  const [name, setName] = useState("");
  const [sku, setSku] = useState("");
  const [price, setPrice] = useState("0");
  const [brandId, setBrandId] = useState("none");
  const [status, setStatus] = useState<CatalogStatus>("draft");
  const [description, setDescription] = useState("");
  const [categoryIds, setCategoryIds] = useState<number[]>([]);
  const [error, setError] = useState<string | null>(null);

  function hydrate(product: Product) {
    const def = product.variants.find((v) => v.is_default) ?? product.variants[0];
    setName(product.name);
    setSku(def?.sku ?? "");
    setPrice(String(def?.price ?? "0"));
    setBrandId(product.brand ? String(product.brand.id) : "none");
    setStatus(product.status);
    setDescription(product.description ?? "");
    setCategoryIds(product.categories.map((c) => c.id));
  }

  useEffect(() => {
    if (!open) return;
    const frame = requestAnimationFrame(() => {
      setError(null);
      if (!isEdit) {
        setName("");
        setSku("");
        setPrice("0");
        setBrandId("none");
        setStatus("draft");
        setDescription("");
        setCategoryIds([]);
        return;
      }
      const product = existing.data?.data;
      if (!product) return;
      hydrate(product);
    });
    return () => cancelAnimationFrame(frame);
  }, [open, isEdit, existing.data]);

  const save = useMutation({
    mutationFn: async () => {
      if (isEdit) {
        return updateProduct(productId!, {
          name,
          brand_id: brandId === "none" ? null : Number(brandId),
          status,
          description: description || null,
          category_ids: categoryIds,
        });
      }
      return createProduct({
        name,
        brand_id: brandId === "none" ? null : Number(brandId),
        status,
        description: description || null,
        category_ids: categoryIds,
        variants: [
          {
            sku,
            price: Number(price),
            is_default: true,
          },
        ],
      });
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "catalog", "products"],
      });
      onOpenChange(false);
    },
    onError: (err) => setError(catalogErrorMessage(err)),
  });

  return (
    <ModalForm
      open={open}
      onOpenChange={onOpenChange}
      title={isEdit ? "Edit Product" : "Add New Product"}
      description="Configure catalog item specifications. Variant images remain on the full editor."
      footer={
        <>
          <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <div className="flex gap-2">
            {isEdit && productId ? (
              <Button asChild variant="outline">
                <Link href={`/admin/catalog/products/${productId}`}>
                  Open full editor
                </Link>
              </Button>
            ) : null}
            <Button
              type="submit"
              form="product-modal-form"
              disabled={save.isPending || (isEdit && existing.isPending)}
            >
              {save.isPending ? "Saving…" : isEdit ? "Save Product" : "Save Product"}
            </Button>
          </div>
        </>
      }
    >
      {isEdit && existing.isPending ? (
        <p className="text-sm text-[#71717A]">Loading…</p>
      ) : (
        <form
          id="product-modal-form"
          className="space-y-4"
          onSubmit={(e: FormEvent) => {
            e.preventDefault();
            setError(null);
            void save.mutateAsync();
          }}
        >
          <div className="space-y-1.5">
            <Label htmlFor="product-name">Product Name *</Label>
            <Input
              id="product-name"
              required
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="e.g., Brushed Alpaca Minimalist Coat"
            />
          </div>
          <div className="space-y-1.5">
            <Label htmlFor="product-description">Description</Label>
            <Textarea
              id="product-description"
              rows={3}
              value={description}
              onChange={(e) => setDescription(e.target.value)}
            />
          </div>
          {!isEdit ? (
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-1.5">
                <Label htmlFor="product-sku">SKU Code *</Label>
                <Input
                  id="product-sku"
                  required
                  className="font-mono"
                  value={sku}
                  onChange={(e) => setSku(e.target.value)}
                  placeholder="ATL-ITEM-XXXX"
                />
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="product-price">Retail Price *</Label>
                <Input
                  id="product-price"
                  required
                  type="number"
                  min={0}
                  step="0.01"
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                />
              </div>
            </div>
          ) : null}
          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-1.5">
              <Label>Brand</Label>
              <Select value={brandId} onValueChange={setBrandId}>
                <SelectTrigger>
                  <SelectValue placeholder="Brand" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">None</SelectItem>
                  {(brands.data?.data ?? []).map((b) => (
                    <SelectItem key={b.id} value={String(b.id)}>
                      {b.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-1.5">
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
          <div className="space-y-1.5">
            <Label>Categories</Label>
            <div className="flex max-h-32 flex-wrap gap-2 overflow-y-auto rounded-md border p-2">
              {(categories.data?.data ?? []).map((c) => {
                const checked = categoryIds.includes(c.id);
                return (
                  <label key={c.id} className="flex items-center gap-1 text-sm">
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
          {error ? (
            <p className="text-sm text-[#ba1a1a]" role="alert">
              {error}
            </p>
          ) : null}
        </form>
      )}
    </ModalForm>
  );
}
