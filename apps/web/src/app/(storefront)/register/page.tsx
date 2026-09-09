import type { Metadata } from "next";

import { RegisterForm } from "@/components/storefront/register-form";

export const metadata: Metadata = {
  title: "Đăng ký",
  robots: { index: false, follow: false },
};

export default function RegisterPage() {
  return (
    <main className="mx-auto max-w-md px-4 py-16">
      <h1 className="text-2xl font-semibold">Đăng ký</h1>
      <div className="mt-8">
        <RegisterForm />
      </div>
    </main>
  );
}
