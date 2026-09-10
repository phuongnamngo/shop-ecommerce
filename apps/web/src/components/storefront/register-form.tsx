"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { FormEvent, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import { mergeGuestCartIfPresent } from "@/lib/api/storefront/cart";
import { registerCustomer } from "@/lib/api/storefront/customer";
import { sfInput } from "@/lib/storefront/ui";

export function RegisterForm() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [pending, setPending] = useState(false);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setPending(true);
    try {
      await registerCustomer({
        name,
        email,
        ...(phone.trim() !== "" ? { phone: phone.trim() } : {}),
        password,
        password_confirmation: passwordConfirmation,
      });
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
        <Label htmlFor="name">Họ tên</Label>
        <Input
          id="name"
          required
          className={sfInput}
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </div>
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
      <div>
        <Label htmlFor="phone">Số điện thoại</Label>
        <Input
          id="phone"
          className={sfInput}
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />
      </div>
      <div>
        <Label htmlFor="password">Mật khẩu</Label>
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
        {pending ? "Đang đăng ký…" : "Đăng ký"}
      </Button>
      <p className="text-center text-sm text-slate-600">
        Đã có tài khoản?{" "}
        <Link href="/login" className="font-semibold text-blue-600">
          Đăng nhập
        </Link>
      </p>
    </form>
  );
}
