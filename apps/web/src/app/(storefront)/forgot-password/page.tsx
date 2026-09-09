import type { Metadata } from "next";

import { ForgotPasswordForm } from "@/components/storefront/forgot-password-form";

export const metadata: Metadata = {
  title: "Quên mật khẩu",
  robots: { index: false, follow: false },
};

export default function ForgotPasswordPage() {
  return (
    <main className="mx-auto max-w-md px-4 py-16">
      <h1 className="text-2xl font-semibold">Quên mật khẩu</h1>
      <div className="mt-8">
        <ForgotPasswordForm />
      </div>
    </main>
  );
}
