"use client";

import Link from "next/link";
import { FormEvent, Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { LayoutGrid } from "lucide-react";

import { useAdminMe } from "@/hooks/use-admin-me";
import { adminLogin, messageForAuthError } from "@/lib/api/admin-auth";
import { adminMeQueryKey } from "@/lib/admin/query-keys";

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const queryClient = useQueryClient();
  const me = useAdminMe();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(true);
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const forbiddenError =
    searchParams.get("error") === "forbidden"
      ? "You do not have permission to access Admin."
      : null;
  const error = formError ?? forbiddenError;

  useEffect(() => {
    if (me.isSuccess && me.data) {
      router.replace("/admin");
    }
  }, [me.isSuccess, me.data, router]);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFormError(null);
    setSubmitting(true);
    try {
      await adminLogin({ email, password });
      await queryClient.invalidateQueries({ queryKey: adminMeQueryKey });
      router.replace("/admin");
    } catch (err) {
      setFormError(messageForAuthError(err));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="relative flex min-h-screen flex-col items-center justify-center p-4 sm:p-6">
      <div className="pointer-events-none fixed top-1/4 left-1/2 z-0 h-[520px] w-[520px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-[#4379ee]/10 blur-3xl" />
      <div className="relative z-10 w-full max-w-[460px] rounded-2xl border border-slate-700/60 bg-[#212b3b] p-7 shadow-[0_20px_45px_-10px_rgba(7,13,23,0.65),0_0_0_1px_rgba(255,255,255,0.05)] sm:p-9">
        <div className="mb-8 flex items-center justify-center gap-3">
          <div className="flex size-10 items-center justify-center rounded-xl bg-gradient-to-tr from-[#4379ee] to-cyan-400 p-[2px] shadow-lg shadow-[#4379ee]/20">
            <div className="flex size-full items-center justify-center rounded-[10px] bg-[#212b3b] text-[#5d87ff]">
              <LayoutGrid className="size-5 fill-current" />
            </div>
          </div>
          <span className="text-2xl font-bold tracking-tight text-white">
            Tailwindadmin
          </span>
        </div>

        <div className="mb-7 grid grid-cols-2 gap-3.5">
          <button
            type="button"
            disabled
            title="Social sign-in is not enabled for this admin API"
            className="flex h-11 items-center justify-center gap-2.5 rounded-xl border border-slate-700/80 bg-[#1b2433] text-sm font-medium text-white opacity-60"
          >
            <svg className="size-4 shrink-0" viewBox="0 0 24 24">
              <path
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                fill="#4285F4"
              />
              <path
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                fill="#34A853"
              />
              <path
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
                fill="#FBBC05"
              />
              <path
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
                fill="#EA4335"
              />
            </svg>
            Google
          </button>
          <button
            type="button"
            disabled
            title="Social sign-in is not enabled for this admin API"
            className="flex h-11 items-center justify-center gap-2.5 rounded-xl border border-slate-700/80 bg-[#1b2433] text-sm font-medium text-white opacity-60"
          >
            <svg className="size-4 shrink-0 fill-[#1877F2]" viewBox="0 0 24 24">
              <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
            </svg>
            Facebook
          </button>
        </div>

        <div className="relative mb-6 flex items-center justify-center">
          <div className="w-full border-t border-slate-700/70" />
          <span className="absolute bg-[#212b3b] px-3.5 text-xs font-medium text-slate-400">
            or sign in with
          </span>
        </div>

        <form className="space-y-4" onSubmit={onSubmit}>
          <div>
            <label
              className="mb-1.5 block text-sm font-semibold text-slate-200"
              htmlFor="email"
            >
              Username
            </label>
            <input
              id="email"
              type="email"
              autoComplete="username"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="admin@ateliercommerce.com"
              className="h-11 w-full rounded-xl border border-slate-700/80 bg-[#182232] px-4 text-sm text-white outline-none placeholder:text-slate-500 focus:border-[#4379ee] focus:ring-2 focus:ring-[#4379ee]/20"
            />
          </div>
          <div>
            <label
              className="mb-1.5 block text-sm font-semibold text-slate-200"
              htmlFor="password"
            >
              Password
            </label>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="••••••••••••"
              className="h-11 w-full rounded-xl border border-slate-700/80 bg-[#182232] px-4 text-sm tracking-wider text-white outline-none placeholder:text-slate-500 focus:border-[#4379ee] focus:ring-2 focus:ring-[#4379ee]/20"
            />
          </div>
          <div className="flex items-center justify-between pt-1 text-sm">
            <label className="flex cursor-pointer items-center gap-2.5 select-none">
              <input
                type="checkbox"
                checked={remember}
                onChange={(e) => setRemember(e.target.checked)}
                className="size-4 rounded border-slate-600 bg-[#182232] text-[#4379ee]"
              />
              <span className="text-xs text-slate-300 sm:text-sm">
                Remember this Device
              </span>
            </label>
            <Link
              href="/admin/forgot-password"
              className="text-xs font-semibold text-[#5d87ff] hover:text-[#8babff] sm:text-sm"
            >
              Forgot Password ?
            </Link>
          </div>
          {error ? (
            <p className="text-sm text-rose-400" role="alert">
              {error}
            </p>
          ) : null}
          <button
            type="submit"
            disabled={submitting}
            className="mt-4 flex h-11 w-full items-center justify-center rounded-xl bg-[#4379ee] text-sm font-semibold text-white shadow-md shadow-[#4379ee]/25 transition-all duration-150 hover:bg-[#5d87ff] active:scale-[0.99] disabled:opacity-70"
          >
            {submitting ? "Signing in…" : "Sign in"}
          </button>
        </form>
      </div>
    </div>
  );
}

export default function AdminLoginPage() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center text-sm text-slate-400">
          Loading…
        </div>
      }
    >
      <LoginForm />
    </Suspense>
  );
}
