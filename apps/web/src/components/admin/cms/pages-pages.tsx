"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import Link from "next/link";

import { ConfirmDeleteButton } from "@/components/admin/catalog/confirm-delete-button";
import { RequireCmsManage } from "@/components/admin/require-cms-manage";
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
import { canManageCms } from "@/lib/admin/can-manage-cms";
import { cmsErrorMessage } from "@/lib/api/cms/errors";
import {
  createCmsPage,
  deleteCmsPage,
  getCmsPage,
  listCmsPages,
  updateCmsPage,
} from "@/lib/api/cms/pages";
import type { CmsPage, CmsPageStatus } from "@/lib/api/cms/types";

export function CmsPagesListPage() {
  const me = useAdminMe();
  const queryClient = useQueryClient();
  const manage = me.isSuccess && me.data ? canManageCms(me.data.roles) : false;
  const [page, setPage] = useState(1);
  const [status, setStatus] = useState<string>("all");

  const query = useQuery({
    queryKey: ["admin", "cms", "pages", page, status],
    queryFn: () =>
      listCmsPages({
        page,
        per_page: 20,
        status: status === "all" ? undefined : status,
      }),
  });

  const rows = query.data?.data ?? [];
  const meta = query.data?.meta;

  return (
    <div className="space-y-6">
      <PageHeader
        title="Page Management"
        description="Markdown content pages shown on the storefront."
        actions={
          manage ? (
            <Button asChild>
              <Link href="/admin/cms/pages/new">+ Add Page</Link>
            </Button>
          ) : null
        }
      />

      <FilterBar>
        <Select
          value={status}
          onValueChange={(value) => {
            setPage(1);
            setStatus(value);
          }}
        >
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All statuses</SelectItem>
            <SelectItem value="draft">draft</SelectItem>
            <SelectItem value="published">published</SelectItem>
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
          <EmptyState title="No pages yet" />
        ) : (
          <>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Title</TableHead>
                  <TableHead>Slug</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {rows.map((cmsPage) => (
                  <TableRow key={cmsPage.id}>
                    <TableCell className="font-medium">{cmsPage.title}</TableCell>
                    <TableCell className="font-mono text-xs">
                      {cmsPage.slug}
                    </TableCell>
                    <TableCell>
                      <StatusBadge status={cmsPage.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      {manage ? (
                        <div className="flex justify-end gap-1">
                          <Button asChild variant="ghost" size="sm">
                            <Link href={`/admin/cms/pages/${cmsPage.id}`}>
                              Edit
                            </Link>
                          </Button>
                          <ConfirmDeleteButton
                            label="Delete"
                            title="Delete page"
                            onConfirm={async () => {
                              await deleteCmsPage(cmsPage.id);
                              await queryClient.invalidateQueries({
                                queryKey: ["admin", "cms", "pages"],
                              });
                            }}
                          />
                        </div>
                      ) : (
                        <Button asChild variant="ghost" size="sm">
                          <Link href={`/admin/cms/pages/${cmsPage.id}`}>
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
    </div>
  );
}

function CmsPageFormFields({
  mode,
  pageId,
  initial,
}: {
  mode: "create" | "edit";
  pageId?: number;
  initial: Pick<CmsPage, "title" | "slug" | "body" | "status">;
}) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const [title, setTitle] = useState(initial.title);
  const [slug, setSlug] = useState(initial.slug ?? "");
  const [body, setBody] = useState(initial.body ?? "");
  const [status, setStatus] = useState<CmsPageStatus>(initial.status);
  const [error, setError] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: async () => {
      const payload = {
        title,
        body,
        slug: slug || null,
        status,
      };
      if (mode === "create") {
        return createCmsPage(payload);
      }
      return updateCmsPage(pageId!, payload);
    },
    onSuccess: async () => {
      await queryClient.invalidateQueries({
        queryKey: ["admin", "cms", "pages"],
      });
      router.replace("/admin/cms/pages");
    },
    onError: (err) => setError(cmsErrorMessage(err)),
  });

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    await save.mutateAsync();
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>{mode === "create" ? "Add page" : "Edit page"}</CardTitle>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
          <div className="space-y-2">
            <Label htmlFor="title">Title</Label>
            <Input
              id="title"
              required
              value={title}
              onChange={(e) => setTitle(e.target.value)}
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
            <Label htmlFor="body">Body (Markdown)</Label>
            <Textarea
              id="body"
              required
              rows={16}
              value={body}
              onChange={(e) => setBody(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label>Status</Label>
            <Select
              value={status}
              onValueChange={(v) => setStatus(v as CmsPageStatus)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="draft">draft</SelectItem>
                <SelectItem value="published">published</SelectItem>
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
            <Button asChild type="button" variant="outline">
              <Link href="/admin/cms/pages">Cancel</Link>
            </Button>
            {mode === "edit" && pageId ? (
              <ConfirmDeleteButton
                onConfirm={async () => {
                  try {
                    await deleteCmsPage(pageId);
                    await queryClient.invalidateQueries({
                      queryKey: ["admin", "cms", "pages"],
                    });
                    router.replace("/admin/cms/pages");
                  } catch (err) {
                    throw new Error(cmsErrorMessage(err));
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

function CmsPageForm({
  mode,
  pageId,
}: {
  mode: "create" | "edit";
  pageId?: number;
}) {
  const me = useAdminMe();
  const manage = me.isSuccess && me.data ? canManageCms(me.data.roles) : false;

  const existing = useQuery({
    queryKey: ["admin", "cms", "pages", pageId],
    queryFn: () => getCmsPage(pageId!),
    enabled: mode === "edit" && !!pageId,
  });

  if (mode === "create") {
    return (
      <RequireCmsManage redirectTo="/admin/cms/pages">
        <CmsPageFormFields
          mode="create"
          initial={{ title: "", slug: "", body: "", status: "draft" }}
        />
      </RequireCmsManage>
    );
  }

  if (existing.isPending) {
    return <p className="text-sm text-muted-foreground">Đang tải…</p>;
  }
  if (existing.isError || !existing.data?.data) {
    return (
      <p className="text-sm text-destructive">
        {cmsErrorMessage(existing.error)}
      </p>
    );
  }

  const cmsPage = existing.data.data;
  if (!manage) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>{cmsPage.title}</CardTitle>
          <CardDescription>Chỉ xem (staff).</CardDescription>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p>Slug: {cmsPage.slug}</p>
          <p>Status: {cmsPage.status}</p>
          <pre className="whitespace-pre-wrap rounded-md border p-3 text-xs">
            {cmsPage.body}
          </pre>
          <Button asChild variant="outline">
            <Link href="/admin/cms/pages">Quay lại</Link>
          </Button>
        </CardContent>
      </Card>
    );
  }

  return (
    <CmsPageFormFields
      key={cmsPage.id}
      mode="edit"
      pageId={pageId}
      initial={cmsPage}
    />
  );
}

export function CmsPageCreatePage() {
  return <CmsPageForm mode="create" />;
}

export function CmsPageEditPage({ pageId }: { pageId: number }) {
  return <CmsPageForm mode="edit" pageId={pageId} />;
}
