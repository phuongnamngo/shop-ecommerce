"use client";

import { FormEvent, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import {
  fetchCustomerMe,
  patchCustomerMe,
} from "@/lib/api/storefront/customer";

export function AccountProfileForm() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [pending, setPending] = useState(false);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void fetchCustomerMe()
        .then((me) => {
          setName(me.name);
          setEmail(me.email);
          setPhone(me.phone ?? "");
        })
        .catch((err) => setError(storefrontErrorMessage(err)));
    });
    return () => cancelAnimationFrame(frame);
  }, []);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSaved(false);
    setPending(true);
    try {
      const me = await patchCustomerMe({
        name,
        phone: phone.trim() === "" ? null : phone.trim(),
      });
      setName(me.name);
      setPhone(me.phone ?? "");
      setSaved(true);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  return (
    <form className="max-w-md space-y-4" onSubmit={(e) => void onSubmit(e)}>
      <h1 className="text-2xl font-semibold">Hồ sơ</h1>
      <div>
        <Label htmlFor="name">Họ tên</Label>
        <Input
          id="name"
          required
          className="mt-1"
          value={name}
          onChange={(e) => setName(e.target.value)}
        />
      </div>
      <div>
        <Label htmlFor="email">Email</Label>
        <Input
          id="email"
          className="mt-1"
          value={email}
          disabled
          readOnly
        />
      </div>
      <div>
        <Label htmlFor="phone">Số điện thoại</Label>
        <Input
          id="phone"
          className="mt-1"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />
      </div>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {saved ? (
        <p className="text-sm text-zinc-600">Đã lưu hồ sơ.</p>
      ) : null}
      <Button type="submit" disabled={pending}>
        {pending ? "Đang lưu…" : "Lưu"}
      </Button>
    </form>
  );
}
