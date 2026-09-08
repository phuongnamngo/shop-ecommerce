"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";

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
  adminForgotPassword,
  messageForAuthError,
} from "@/lib/api/admin-auth";

export default function AdminForgotPasswordPage() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await adminForgotPassword({ email });
      setDone(true);
    } catch (err) {
      setError(messageForAuthError(err));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Quên mật khẩu</CardTitle>
        <CardDescription>
          Nhập email Admin. Nếu tài khoản tồn tại, hệ thống sẽ gửi link đặt lại.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {done ? (
          <div className="space-y-4 text-sm">
            <p>
              Nếu email hợp lệ, hướng dẫn đặt lại mật khẩu đã được gửi. Kiểm tra
              hộp thư (local: mail log).
            </p>
            <Link href="/admin/login" className="underline">
              Về trang đăng nhập
            </Link>
          </div>
        ) : (
          <form className="space-y-4" onSubmit={onSubmit}>
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />
            </div>
            {error ? (
              <p className="text-sm text-destructive" role="alert">
                {error}
              </p>
            ) : null}
            <Button type="submit" className="w-full" disabled={submitting}>
              {submitting ? "Đang gửi…" : "Gửi link"}
            </Button>
            <p className="text-center text-sm text-muted-foreground">
              <Link href="/admin/login" className="underline">
                Quay lại login
              </Link>
            </p>
          </form>
        )}
      </CardContent>
    </Card>
  );
}
