"use client";

import Link from "next/link";
import { FormEvent, Suspense, useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";

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
  adminResetPassword,
  messageForAuthError,
} from "@/lib/api/admin-auth";

function ResetPasswordForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const email = searchParams.get("email")?.trim() ?? "";
  const token = searchParams.get("token")?.trim() ?? "";
  const missingQuery = !email || !token;

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(
    missingQuery ? "Link đặt lại mật khẩu thiếu token hoặc email." : null,
  );
  const [submitting, setSubmitting] = useState(false);

  const canSubmit = useMemo(
    () => !missingQuery && password.length > 0 && passwordConfirmation.length > 0,
    [missingQuery, password, passwordConfirmation],
  );

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (missingQuery) {
      return;
    }
    if (password !== passwordConfirmation) {
      setError("Mật khẩu xác nhận không khớp.");
      return;
    }
    setError(null);
    setSubmitting(true);
    try {
      await adminResetPassword({
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.replace("/admin/login");
    } catch (err) {
      setError(messageForAuthError(err));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Đặt lại mật khẩu</CardTitle>
        <CardDescription>Nhập mật khẩu mới cho tài khoản Admin.</CardDescription>
      </CardHeader>
      <CardContent>
        <form className="space-y-4" onSubmit={onSubmit}>
          <div className="space-y-2">
            <Label htmlFor="email">Email</Label>
            <Input id="email" type="email" value={email} readOnly disabled />
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">Password mới</Label>
            <Input
              id="password"
              type="password"
              autoComplete="new-password"
              required
              disabled={missingQuery}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="password_confirmation">Xác nhận password</Label>
            <Input
              id="password_confirmation"
              type="password"
              autoComplete="new-password"
              required
              disabled={missingQuery}
              value={passwordConfirmation}
              onChange={(e) => setPasswordConfirmation(e.target.value)}
            />
          </div>
          {error ? (
            <p className="text-sm text-destructive" role="alert">
              {error}
            </p>
          ) : null}
          <Button
            type="submit"
            className="w-full"
            disabled={!canSubmit || submitting}
          >
            {submitting ? "Đang lưu…" : "Đặt lại mật khẩu"}
          </Button>
          <p className="text-center text-sm text-muted-foreground">
            <Link href="/admin/login" className="underline">
              Về login
            </Link>
          </p>
        </form>
      </CardContent>
    </Card>
  );
}

export default function AdminResetPasswordPage() {
  return (
    <Suspense
      fallback={
        <Card>
          <CardHeader>
            <CardTitle>Đặt lại mật khẩu</CardTitle>
            <CardDescription>Đang tải…</CardDescription>
          </CardHeader>
        </Card>
      }
    >
      <ResetPasswordForm />
    </Suspense>
  );
}
