import type { Metadata } from "next";
import { Suspense } from "react";

import { AuthShell } from "@/components/storefront/auth-shell";
import { LoginForm } from "@/components/storefront/login-form";

export const metadata: Metadata = {
  title: "Đăng nhập",
  robots: { index: false, follow: false },
};

export default function LoginPage() {
  return (
    <AuthShell
      title="Chào mừng trở lại"
      subtitle="Đăng nhập để quản lý đơn hàng và trải nghiệm mua sắm tốt hơn."
    >
      <Suspense fallback={<p className="text-sm text-slate-500">Đang tải…</p>}>
        <LoginForm />
      </Suspense>
    </AuthShell>
  );
}
