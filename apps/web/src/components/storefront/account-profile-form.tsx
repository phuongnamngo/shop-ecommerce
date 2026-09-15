"use client";

import { FormEvent, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { storefrontErrorMessage } from "@/lib/api/storefront/browser";
import {
  fetchCustomerMe,
  patchCustomerMe,
  sendCustomerPhoneOtp,
  verifyCustomerPhoneOtp,
} from "@/lib/api/storefront/customer";
import type { CustomerProfile } from "@/lib/api/storefront/types";
import { sfInput } from "@/lib/storefront/ui";

export function AccountProfileForm() {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [persistedPhone, setPersistedPhone] = useState<string | null>(null);
  const [phoneVerifiedAt, setPhoneVerifiedAt] = useState<string | null>(null);
  const [otpCode, setOtpCode] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [saved, setSaved] = useState(false);
  const [pending, setPending] = useState(false);
  const [otpPending, setOtpPending] = useState(false);

  function applyProfile(me: CustomerProfile) {
    setName(me.name);
    setEmail(me.email);
    setPhone(me.phone ?? "");
    setPersistedPhone(me.phone);
    setPhoneVerifiedAt(me.phone_verified_at);
  }

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void fetchCustomerMe()
        .then(applyProfile)
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
      applyProfile(me);
      setOtpCode("");
      setSaved(true);
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setPending(false);
    }
  }

  async function onSendOtp() {
    setError(null);
    setOtpPending(true);
    try {
      await sendCustomerPhoneOtp();
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setOtpPending(false);
    }
  }

  async function onVerifyOtp() {
    setError(null);
    setOtpPending(true);
    try {
      const me = await verifyCustomerPhoneOtp(otpCode);
      applyProfile(me);
      setOtpCode("");
    } catch (err) {
      setError(storefrontErrorMessage(err));
    } finally {
      setOtpPending(false);
    }
  }

  const showOtp = persistedPhone !== null && persistedPhone !== "";
  const verified = phoneVerifiedAt !== null;

  return (
    <form className="max-w-xl space-y-4 rounded-xl border border-slate-200 bg-white p-5" onSubmit={(e) => void onSubmit(e)}>
      <h1 className="text-2xl font-bold tracking-tight">Thông tin cá nhân</h1>
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
          className={sfInput}
          value={email}
          disabled
          readOnly
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
        {showOtp ? (
          <div className="mt-3 space-y-2">
            <p className="text-sm text-slate-600">
              {verified ? "Đã xác minh" : "Chưa xác minh"}
            </p>
            {verified ? null : (
              <div className="flex flex-col gap-2 sm:flex-row sm:items-end">
                <Button
                  type="button"
                  variant="outline"
                  className="h-11"
                  disabled={otpPending}
                  onClick={() => void onSendOtp()}
                >
                  Gửi mã
                </Button>
                <div className="min-w-0 flex-1">
                  <Label htmlFor="phone-otp">Mã OTP</Label>
                  <Input
                    id="phone-otp"
                    inputMode="numeric"
                    autoComplete="one-time-code"
                    className={sfInput}
                    value={otpCode}
                    onChange={(e) => setOtpCode(e.target.value)}
                  />
                </div>
                <Button
                  type="button"
                  className="h-11 rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
                  disabled={otpPending || otpCode.length !== 6}
                  onClick={() => void onVerifyOtp()}
                >
                  Xác nhận
                </Button>
              </div>
            )}
          </div>
        ) : null}
      </div>
      {error ? (
        <p className="text-sm text-red-700" role="alert">
          {error}
        </p>
      ) : null}
      {saved ? (
        <p className="text-sm text-emerald-700">Đã lưu hồ sơ.</p>
      ) : null}
      <Button
        type="submit"
        className="h-11 rounded-lg bg-blue-600 font-semibold hover:bg-blue-700"
        disabled={pending}
      >
        {pending ? "Đang lưu…" : "Lưu"}
      </Button>
    </form>
  );
}
