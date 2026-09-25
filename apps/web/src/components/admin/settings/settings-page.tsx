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
import {
  adminTwoFactorConfirm,
  adminTwoFactorSetup,
} from "@/lib/api/admin-auth";
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

function TwoFactorCard() {
  const [secret, setSecret] = useState<string | null>(null);
  const [otpauthUri, setOtpauthUri] = useState<string | null>(null);
  const [code, setCode] = useState("");
  const [recoveryCodes, setRecoveryCodes] = useState<string[] | null>(null);
  const [formError, setFormError] = useState<string | null>(null);

  const setup = useMutation({
    mutationFn: adminTwoFactorSetup,
    onSuccess: (result) => {
      setSecret(result.data.secret);
      setOtpauthUri(result.data.otpauth_uri);
      setRecoveryCodes(null);
      setFormError(null);
    },
    onError: (error) => setFormError(settingErrorMessage(error)),
  });

  const confirm = useMutation({
    mutationFn: () => adminTwoFactorConfirm({ code }),
    onSuccess: (result) => {
      setRecoveryCodes(result.data.recovery_codes);
      setFormError(null);
    },
    onError: (error) => setFormError(settingErrorMessage(error)),
  });

  return (
    <Card>
      <CardHeader>
        <CardTitle>Xác thực hai lớp</CardTitle>
        <CardDescription>
          Bật TOTP cho tài khoản admin đang đăng nhập. Recovery code chỉ hiện một lần.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4">
        <Button type="button" onClick={() => setup.mutate()} disabled={setup.isPending}>
          Bật 2FA
        </Button>
        {secret ? (
          <div className="space-y-2 text-sm">
            <p>Secret: {secret}</p>
            <p className="break-all">otpauth_uri: {otpauthUri}</p>
            <form
              className="space-y-2"
              onSubmit={(event) => {
                event.preventDefault();
                confirm.mutate();
              }}
            >
              <Label htmlFor="totp-code">Code</Label>
              <Input
                id="totp-code"
                value={code}
                onChange={(event) => setCode(event.target.value)}
                required
              />
              <Button type="submit" disabled={confirm.isPending}>
                Xác nhận
              </Button>
            </form>
          </div>
        ) : null}
        {recoveryCodes ? (
          <ul className="space-y-1 text-sm">
            {recoveryCodes.map((item) => (
              <li key={item}>{item}</li>
            ))}
          </ul>
        ) : null}
        {formError ? <p className="text-sm text-rose-600">{formError}</p> : null}
      </CardContent>
    </Card>
  );
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
      <TwoFactorCard />
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
