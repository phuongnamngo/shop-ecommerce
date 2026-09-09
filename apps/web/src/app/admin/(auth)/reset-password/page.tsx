"use client";

import Link from "next/link";
import { FormEvent, Suspense, useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { ArrowLeft, Shield } from "lucide-react";

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
    missingQuery ? "This reset link is missing a token or email." : null,
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
      setError("Password confirmation does not match.");
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
    <div className="relative flex min-h-screen flex-col bg-[#151d2a]">
      <header className="relative z-10 px-4 py-5 sm:px-8">
        <Link
          href="/admin/login"
          className="group inline-flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-white"
        >
          <ArrowLeft className="size-[18px] transition-transform duration-150 group-hover:-translate-x-1" />
          Back to Sign In
        </Link>
      </header>
      <main className="flex flex-1 items-center justify-center px-4 py-8">
        <div className="w-full max-w-[460px] rounded-2xl border border-[#2e3a4e] bg-[#1e293b] p-7 sm:p-9">
          <div className="mb-7 flex items-center gap-3">
            <div className="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#5d87ff] to-[#1f53c9] text-white">
              <Shield className="size-[22px]" />
            </div>
            <div>
              <span className="text-lg font-bold tracking-tight text-white">
                Atelier<span className="text-[#5d87ff]">Admin</span>
              </span>
              <span className="block text-[11px] tracking-wide text-slate-400 uppercase">
                Enterprise Suite
              </span>
            </div>
          </div>
          <h1 className="mb-2 text-2xl font-bold text-white">Set a new password</h1>
          <p className="mb-6 text-sm text-[#94a3b8]">
            Choose a new password for this administrator account.
          </p>
          <form className="space-y-4" onSubmit={onSubmit}>
            <div>
              <label className="mb-2 block text-xs font-semibold tracking-wider text-slate-300 uppercase">
                Email
              </label>
              <input
                type="email"
                value={email}
                readOnly
                disabled
                className="h-11 w-full rounded-xl border border-[#2e3a4e] bg-[#182232] px-4 text-sm text-slate-400"
              />
            </div>
            <div>
              <label className="mb-2 block text-xs font-semibold tracking-wider text-slate-300 uppercase">
                New password
              </label>
              <input
                type="password"
                autoComplete="new-password"
                required
                disabled={missingQuery}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="h-11 w-full rounded-xl border border-[#2e3a4e] bg-[#182232] px-4 text-sm text-white outline-none focus:border-[#5d87ff] focus:ring-2 focus:ring-[#5d87ff]/20"
              />
            </div>
            <div>
              <label className="mb-2 block text-xs font-semibold tracking-wider text-slate-300 uppercase">
                Confirm password
              </label>
              <input
                type="password"
                autoComplete="new-password"
                required
                disabled={missingQuery}
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                className="h-11 w-full rounded-xl border border-[#2e3a4e] bg-[#182232] px-4 text-sm text-white outline-none focus:border-[#5d87ff] focus:ring-2 focus:ring-[#5d87ff]/20"
              />
            </div>
            {error ? (
              <p className="text-sm text-rose-400" role="alert">
                {error}
              </p>
            ) : null}
            <button
              type="submit"
              disabled={!canSubmit || submitting}
              className="flex h-11 w-full items-center justify-center rounded-xl bg-gradient-to-r from-[#5d87ff] to-[#4379ee] text-sm font-semibold text-white disabled:opacity-70"
            >
              {submitting ? "Saving…" : "Update password"}
            </button>
          </form>
        </div>
      </main>
    </div>
  );
}

export default function AdminResetPasswordPage() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center text-sm text-slate-400">
          Loading…
        </div>
      }
    >
      <ResetPasswordForm />
    </Suspense>
  );
}
