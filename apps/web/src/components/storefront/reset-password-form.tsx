"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { FormEvent, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { resetCustomerPassword } from "@/lib/api/storefront/customer";
import { sfInput } from "@/lib/storefront/ui";

export function ResetPasswordForm() {
  const router = useRouter();
  const params = useSearchParams();
  const email = params.get("email") ?? "";
  const token = params.get("token") ?? "";
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setPending(true);
    try {
      await resetCustomerPassword({
        email,
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      router.replace("/login");
    } catch (err) {
      setError(storefrontErrorMessage(err));
      setPending(false);
    }
  }

  if (!email || !token) {
    return (
      <p className="text-sm text-red-700" role="alert">
        Link đặt lại mật khẩu không hợp lệ.
      </p>
    );
  }

  return (
    <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <div>
        <Label htmlFor="password">Mật khẩu mới</Label>
        <Input
          id="password"
          type="password"
          autoComplete="new-password"
          required
          className={sfInput}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
      </div>
      <div>
        <Label htmlFor="password_confirmation">Xác nhận mật khẩu</Label>
        <Input
          id="password_confirmation"
          type="password"
          autoComplete="new-password"
          required
          className={sfInput}
          value={passwordConfirmation}
          onChange={(e) => setPasswordConfirmation(e.target.value)}
        />
      </div>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      <Button
        type="submit"
        className="h-12 w-full rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
        disabled={pending}
      >
        {pending ? "Đang lưu…" : "Đặt lại mật khẩu"}
      </Button>
      <p className="text-center text-sm text-slate-600">
        <Link href="/login" className="font-semibold text-blue-600">
          Quay lại đăng nhập
        </Link>
      </p>
    </form>
  );
}
