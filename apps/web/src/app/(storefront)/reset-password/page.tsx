import type { Metadata } from "next";
import { Suspense } from "react";

import { ResetPasswordForm } from "@/components/storefront/reset-password-form";

export const metadata: Metadata = {
  title: "Đặt lại mật khẩu",
  robots: { index: false, follow: false },
};

export default function ResetPasswordPage() {
  return (
    <main className="mx-auto max-w-md px-4 py-16">
      <h1 className="text-2xl font-semibold">Đặt lại mật khẩu</h1>
      <div className="mt-8">
        <Suspense fallback={<p className="text-sm text-zinc-600">Đang tải…</p>}>
          <ResetPasswordForm />
        </Suspense>
      </div>
    </main>
  );
}
