import Link from "next/link";
import type { ReactNode } from "react";
import { ArrowLeft, ShieldCheck, Truck } from "lucide-react";

import { STORE_NAME } from "@/lib/storefront/ui";

export function AuthShell({
  title,
  subtitle,
  children,
}: {
  title: string;
  subtitle?: string;
  children: ReactNode;
}) {
  return (
    <div className="grid min-h-full lg:grid-cols-2">
      <aside className="relative hidden min-h-full overflow-hidden bg-slate-950 text-white lg:flex">
        <div className="absolute inset-0 bg-gradient-to-br from-slate-900 via-slate-950 to-blue-950" />
        <div className="relative flex w-full flex-col justify-between p-12">
          <div>
            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">
              {STORE_NAME} campaign
            </p>
            <p className="mt-16 text-4xl font-bold leading-tight">
              NEW SEASON
              <br />
              ESSENTIALS
            </p>
            <p className="mt-4 max-w-sm text-sm text-white/70">
              Thời trang nam tối giản, dễ mặc mỗi ngày.
            </p>
          </div>
          <ul className="space-y-2 text-sm text-white/80">
            <li className="flex items-center gap-2">
              <ShieldCheck className="h-4 w-4" /> Chính hãng 100%
            </li>
            <li className="flex items-center gap-2">
              <Truck className="h-4 w-4" /> Giao hàng toàn quốc
            </li>
          </ul>
        </div>
      </aside>
      <div className="flex min-h-full flex-col bg-white px-4 py-8 sm:px-10">
        <div className="flex items-center justify-between text-sm">
          <Link
            href="/"
            className="inline-flex items-center gap-1 text-slate-600 hover:text-slate-950"
          >
            <ArrowLeft className="h-4 w-4" />
            Trở về trang chủ
          </Link>
          <Link href="/products" className="text-slate-500 hover:text-slate-950">
            Cửa hàng
          </Link>
        </div>
        <div className="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
          <Link
            href="/"
            className="text-lg font-bold tracking-[0.18em] text-slate-950"
          >
            {STORE_NAME}
          </Link>
          <h1 className="mt-6 text-3xl font-bold tracking-tight">{title}</h1>
          {subtitle ? (
            <p className="mt-2 text-sm text-slate-500">{subtitle}</p>
          ) : null}
          <div className="mt-8">{children}</div>
        </div>
      </div>
    </div>
  );
}
