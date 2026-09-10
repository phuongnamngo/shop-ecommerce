"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";
import { Eye, EyeOff } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { mergeGuestCartIfPresent } from "@/lib/api/storefront/cart";
import { loginCustomer } from "@/lib/api/storefront/customer";
import { sfInput } from "@/lib/storefront/ui";

export function LoginForm() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setPending(true);
    try {
      await loginCustomer({ email, password });
      await mergeGuestCartIfPresent();
      router.replace("/account");
    } catch (err) {
      setError(storefrontErrorMessage(err));
      setPending(false);
    }
  }

  return (
    <form className="space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <div>
        <Label htmlFor="email">Email</Label>
        <Input
          id="email"
          type="email"
          autoComplete="username"
          required
          className={sfInput}
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />
      </div>
      <div>
        <div className="flex items-center justify-between">
          <Label htmlFor="password">Mật khẩu</Label>
          <Link href="/forgot-password" className="text-xs font-semibold text-blue-600">
            Quên mật khẩu?
          </Link>
        </div>
        <div className="relative">
          <Input
            id="password"
            type={showPassword ? "text" : "password"}
            autoComplete="current-password"
            required
            className={`${sfInput} pr-11`}
            value={password}
            onChange={(e) => setPassword(e.target.value)}
          />
          <button
            type="button"
            className="absolute right-2 top-1/2 flex h-11 w-10 -translate-y-1/2 items-center justify-center text-slate-400"
            aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"}
            onClick={() => setShowPassword((v) => !v)}
          >
            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
          </button>
        </div>
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
        {pending ? "Đang đăng nhập…" : "Đăng nhập"}
      </Button>
      <p className="text-center text-sm text-slate-600">
        Chưa có tài khoản?{" "}
        <Link href="/register" className="font-semibold text-blue-600">
          Đăng ký ngay
        </Link>
      </p>
    </form>
  );
}
