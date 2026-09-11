"use client";

import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState, type ReactNode } from "react";

import { AccountNav } from "@/components/storefront/account-nav";
import { fetchCustomerMeOrNull } from "@/lib/api/storefront/customer";
import { sfContainer } from "@/lib/storefront/ui";

export function AccountShell({ children }: { children: ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const [ready, setReady] = useState(false);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void fetchCustomerMeOrNull()
        .then((me) => {
          if (!me) {
            router.replace(`/login?next=${encodeURIComponent(pathname)}`);
            return;
          }
          setReady(true);
        })
        .catch(() => {
          router.replace(`/login?next=${encodeURIComponent(pathname)}`);
        });
    });
    return () => cancelAnimationFrame(frame);
  }, [router, pathname]);

  if (!ready) {
    return (
      <main className={`${sfContainer} py-10`}>
        <p className="text-sm text-slate-500">Đang tải tài khoản…</p>
      </main>
    );
  }

  return (
    <main className={`${sfContainer} py-8 lg:py-10`}>
      <div className="grid gap-6 lg:grid-cols-[15rem_1fr]">
        <AccountNav />
        <div>{children}</div>
      </div>
    </main>
  );
}
