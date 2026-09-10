import type { Metadata } from "next";
import { Suspense } from "react";

import { AuthShell } from "@/components/storefront/auth-shell";
import { ResetPasswordForm } from "@/components/storefront/reset-password-form";

export const metadata: Metadata = {
  title: "Đặt lại mật khẩu",
  robots: { index: false, follow: false },
};

export default function ResetPasswordPage() {
  return (
    <AuthShell
      title="Tạo mật khẩu mới"
      subtitle="Nhập mật khẩu mới cho tài khoản của bạn."
    >
      <Suspense fallback={<p className="text-sm text-slate-500">Đang tải…</p>}>
        <ResetPasswordForm />
      </Suspense>
    </AuthShell>
  );
}
