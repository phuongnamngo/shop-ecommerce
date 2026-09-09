"use client";

import { useRouter } from "next/navigation";
import { useEffect, useState, type ReactNode } from "react";

import { AccountNav } from "@/components/storefront/account-nav";
import { fetchCustomerMeOrNull } from "@/lib/api/storefront/customer";

export function AccountShell({ children }: { children: ReactNode }) {
  const router = useRouter();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void fetchCustomerMeOrNull()
        .then((me) => {
          if (!me) {
            router.replace("/login");
            return;
          }
          setReady(true);
        })
        .catch(() => {
          router.replace("/login");
        });
    });
    return () => cancelAnimationFrame(frame);
  }, [router]);

  if (!ready) {
    return (
      <main className="mx-auto max-w-6xl px-4 py-10">
        <p className="text-sm text-zinc-600">Đang tải tài khoản…</p>
      </main>
    );
  }

  return (
    <main className="mx-auto max-w-6xl px-4 py-10">
      <div className="grid gap-6 md:grid-cols-[10rem_1fr]">
        <AccountNav />
        <div>{children}</div>
      </div>
    </main>
  );
}
