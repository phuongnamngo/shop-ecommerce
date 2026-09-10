import type { Metadata } from "next";

import { AuthShell } from "@/components/storefront/auth-shell";
import { ForgotPasswordForm } from "@/components/storefront/forgot-password-form";

export const metadata: Metadata = {
  title: "Quên mật khẩu",
  robots: { index: false, follow: false },
};

export default function ForgotPasswordPage() {
  return (
    <AuthShell
      title="Quên mật khẩu"
      subtitle="Nhập email để nhận link đặt lại mật khẩu nếu tài khoản tồn tại."
    >
      <ForgotPasswordForm />
    </AuthShell>
  );
}
