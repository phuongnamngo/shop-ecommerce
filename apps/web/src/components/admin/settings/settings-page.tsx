"use client";

import { FormEvent, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";

import { EmptyState, LoadingState } from "@/components/admin/layout/empty-state";
import { PageHeader } from "@/components/admin/layout/page-header";
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
import { useAdminMe } from "@/hooks/use-admin-me";
import { canManageSettings } from "@/lib/admin/can-manage-settings";
import { ApiError } from "@/lib/api/client";
import {
  listAdminSettings,
  patchAdminSetting,
} from "@/lib/api/settings/client";
import type { AdminSetting } from "@/lib/api/settings/types";

function settingErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }
  return "Không lưu được cài đặt.";
}

function stringField(
  rows: AdminSetting[],
  key: string,
  field: "vi" | "code",
): string {
  const row = rows.find((item) => item.key === key);
  const raw = row?.value && field in row.value ? row.value[field] : "";
  return typeof raw === "string" ? raw : "";
}

function SettingsForm({
  initialName,
  initialCode,
  manage,
}: {
  initialName: string;
  initialCode: string;
  manage: boolean;
}) {
  const queryClient = useQueryClient();
  const [siteName, setSiteName] = useState(initialName);
  const [currencyCode, setCurrencyCode] = useState(initialCode);
  const [formError, setFormError] = useState<string | null>(null);

  const save = useMutation({
    mutationFn: async () => {
      await patchAdminSetting("site.name", { vi: siteName });
      await patchAdminSetting("currency.default", {
        code: currencyCode.trim().toUpperCase(),
      });
    },
    onSuccess: async () => {
      setFormError(null);
      await queryClient.invalidateQueries({ queryKey: ["admin", "settings"] });
    },
    onError: (error) => {
      setFormError(settingErrorMessage(error));
    },
  });

  function onSubmit(event: FormEvent) {
    event.preventDefault();
    if (!manage) {
      return;
    }
    setFormError(null);
    save.mutate();
  }

  return (
    <form className="max-w-md space-y-4" onSubmit={onSubmit}>
      <div className="space-y-2">
        <Label htmlFor="site-name">Site name</Label>
        <Input
          id="site-name"
          value={siteName}
          onChange={(event) => setSiteName(event.target.value)}
          maxLength={80}
          disabled={!manage || save.isPending}
        />
      </div>
      <div className="space-y-2">
        <Label htmlFor="currency-code">Currency code</Label>
        <Input
          id="currency-code"
          value={currencyCode}
          onChange={(event) => setCurrencyCode(event.target.value)}
          maxLength={3}
          className="uppercase"
          disabled={!manage || save.isPending}
        />
      </div>
      {formError ? (
        <p className="text-sm text-destructive">{formError}</p>
      ) : null}
      {save.isSuccess && !formError ? (
        <p className="text-sm text-muted-foreground">Đã lưu.</p>
      ) : null}
      {manage ? (
        <Button type="submit" disabled={save.isPending}>
          {save.isPending ? "Đang lưu…" : "Save"}
        </Button>
      ) : (
        <p className="text-sm text-muted-foreground">
          Chỉ xem. Staff không sửa settings.
        </p>
      )}
    </form>
  );
}

export function SettingsPage() {
  const me = useAdminMe();
  const manage =
    me.isSuccess && me.data ? canManageSettings(me.data.roles) : false;

  const query = useQuery({
    queryKey: ["admin", "settings"],
    queryFn: listAdminSettings,
  });

  if (query.isPending) {
    return <LoadingState />;
  }

  if (query.isError) {
    return (
      <EmptyState
        title="Không tải được cài đặt"
        description={settingErrorMessage(query.error)}
      />
    );
  }

  const rows = query.data.data;
  const initialName = stringField(rows, "site.name", "vi");
  const initialCode = stringField(rows, "currency.default", "code");

  return (
    <div className="space-y-6">
      <PageHeader
        title="Settings"
        description="Tên cửa hàng trên storefront và mã tiền tệ lưu trong settings."
      />
      <Card>
        <CardHeader>
          <CardTitle>Site</CardTitle>
          <CardDescription>
            Checkout, đơn và dashboard vẫn dùng VND. Field currency chỉ lưu vào
            settings.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <SettingsForm
            key={`${query.dataUpdatedAt}-${initialName}-${initialCode}`}
            initialName={initialName}
            initialCode={initialCode}
            manage={manage}
          />
        </CardContent>
      </Card>
    </div>
  );
}
