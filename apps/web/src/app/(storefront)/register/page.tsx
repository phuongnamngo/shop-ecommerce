import type { Metadata } from "next";

import { AuthShell } from "@/components/storefront/auth-shell";
import { RegisterForm } from "@/components/storefront/register-form";

export const metadata: Metadata = {
  title: "Đăng ký",
  robots: { index: false, follow: false },
};

export default function RegisterPage() {
  return (
    <AuthShell
      title="Tạo tài khoản"
      subtitle="Đăng ký để lưu địa chỉ, theo dõi đơn hàng và thanh toán nhanh hơn."
    >
      <RegisterForm />
    </AuthShell>
  );
}
