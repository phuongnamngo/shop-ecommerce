"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { forgotCustomerPassword } from "@/lib/api/storefront/customer";
import { sfInput } from "@/lib/storefront/ui";

export function ForgotPasswordForm() {
  const [email, setEmail] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const [pending, setPending] = useState(false);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setPending(true);
    try {
      await forgotCustomerPassword(email);
      setDone(true);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  if (done) {
    return (
      <p className="text-sm text-slate-600">
        Nếu email tồn tại trong hệ thống, chúng tôi đã gửi link đặt lại mật khẩu.
      </p>
    );
  }

  return (
    <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <div>
        <Label htmlFor="email">Email</Label>
        <Input
          id="email"
          type="email"
          autoComplete="email"
          required
          className={sfInput}
          value={email}
          onChange={(e) => setEmail(e.target.value)}
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
        {pending ? "Đang gửi…" : "Gửi link đặt lại"}
      </Button>
      <p className="text-center text-sm text-slate-600">
        <Link href="/login" className="font-semibold text-blue-600">
          Quay lại đăng nhập
        </Link>
      </p>
    </form>
  );
}
