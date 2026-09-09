"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { ArrowLeft, ArrowRight, Mail, Shield, Clock } from "lucide-react";

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
    <div className="relative flex min-h-screen flex-col bg-[#151d2a]">
      <header className="relative z-10 flex items-center justify-between px-4 py-5 sm:px-8">
        <Link
          href="/admin/login"
          className="group flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-white"
        >
          <ArrowLeft className="size-[18px] transition-transform duration-150 group-hover:-translate-x-1" />
          Back to Sign In
        </Link>
      </header>
      <main className="relative z-10 flex flex-1 items-center justify-center px-4 py-8">
        <div className="w-full max-w-[460px]">
          <div className="relative rounded-2xl border border-[#2e3a4e] bg-[#1e293b] p-7 sm:p-9">
            <div className="mb-7 flex items-center gap-3">
              <div className="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#5d87ff] to-[#1f53c9] text-white shadow-lg shadow-[#5d87ff]/30">
                <Shield className="size-[22px]" />
              </div>
              <div>
                <span className="flex items-center text-lg font-bold tracking-tight text-white">
                  Atelier<span className="text-[#5d87ff]">Admin</span>
                </span>
                <span className="block text-[11px] leading-none font-medium tracking-wide text-slate-400 uppercase">
                  Enterprise Suite
                </span>
              </div>
            </div>

            {done ? (
              <div>
                <div className="mb-5 flex size-12 items-center justify-center rounded-xl border border-emerald-500/20 bg-emerald-500/10 text-emerald-400 ring-4 ring-emerald-500/10">
                  <Mail className="size-6" />
                </div>
                <div className="mb-5">
                  <div className="mb-2.5 inline-flex items-center gap-1.5 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-xs font-semibold text-emerald-400">
                    <span className="size-1.5 rounded-full bg-emerald-400" />
                    Link Dispatched
                  </div>
                  <h2 className="mb-2 text-2xl font-bold tracking-tight text-white">
                    Check your inbox
                  </h2>
                  <p className="text-sm leading-relaxed text-[#94a3b8]">
                    If the account exists, a reset link has been sent to{" "}
                    <span className="font-medium text-white">{email}</span>.
                  </p>
                </div>
                <Link
                  href="/admin/login"
                  className="inline-flex items-center gap-2 text-sm font-semibold text-[#5d87ff] hover:text-[#8babff]"
                >
                  <ArrowLeft className="size-4" />
                  Back to Sign In
                </Link>
              </div>
            ) : (
              <>
                <div className="mb-6">
                  <h1 className="mb-2 text-2xl font-bold tracking-tight text-white">
                    Reset Password
                  </h1>
                  <p className="text-sm leading-relaxed text-[#94a3b8]">
                    Enter your registered email address and we will send a
                    password reset link to your inbox.
                  </p>
                </div>
                <form className="space-y-4" onSubmit={onSubmit}>
                  <div>
                    <label
                      className="mb-2 block text-xs font-semibold tracking-wider text-slate-300 uppercase"
                      htmlFor="admin-email"
                    >
                      Email Address
                    </label>
                    <div className="relative">
                      <Mail className="pointer-events-none absolute top-1/2 left-3.5 size-[19px] -translate-y-1/2 text-slate-400" />
                      <input
                        id="admin-email"
                        name="email"
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="admin@example.com"
                        className="h-11 w-full rounded-xl border border-[#2e3a4e] bg-[#182232] py-2.5 pr-4 pl-10 text-sm text-white outline-none placeholder-slate-500 focus:border-[#5d87ff] focus:ring-2 focus:ring-[#5d87ff]/20"
                      />
                    </div>
                    <p className="mt-1.5 flex items-center gap-1 text-[11px] text-slate-400">
                      <Shield className="size-[13px]" />
                      Must match an authorized administrator directory profile.
                    </p>
                  </div>
                  {error ? (
                    <p className="text-sm text-rose-400" role="alert">
                      {error}
                    </p>
                  ) : null}
                  <button
                    type="submit"
                    disabled={submitting}
                    className="mt-2 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#5d87ff] to-[#4379ee] text-sm font-semibold text-white shadow-lg shadow-[#5d87ff]/25 transition-all duration-150 hover:from-[#4379ee] hover:to-[#1f53c9] active:scale-[0.99] disabled:opacity-70"
                  >
                    {submitting ? "Sending…" : "Send Reset Link"}
                    <ArrowRight className="size-[18px]" />
                  </button>
                </form>
                <div className="mt-6 flex items-start gap-2.5 rounded-xl border border-[#2a3547] bg-[#182232] p-3.5">
                  <Clock className="mt-0.5 size-[17px] shrink-0 text-[#5d87ff]" />
                  <p className="text-xs leading-snug text-slate-400">
                    For your protection, reset links are single-use and expire
                    within 15 minutes.
                  </p>
                </div>
              </>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
