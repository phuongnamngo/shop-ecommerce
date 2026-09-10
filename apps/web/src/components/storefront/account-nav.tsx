"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  LogOut,
  MapPin,
  Package,
  UserRound,
  LayoutDashboard,
} from "lucide-react";

import { logoutCustomer } from "@/lib/api/storefront/customer";
import { emitCartChanged } from "@/lib/storefront/cart-events";
import { cn } from "@/lib/utils";

const links = [
  { href: "/account", label: "Tổng quan", icon: LayoutDashboard },
  { href: "/account/profile", label: "Hồ sơ", icon: UserRound },
  { href: "/account/addresses", label: "Địa chỉ", icon: MapPin },
  { href: "/account/orders", label: "Đơn hàng", icon: Package },
];

function isActive(pathname: string, href: string): boolean {
  if (href === "/account") {
    return pathname === "/account";
  }
  return pathname === href || pathname.startsWith(`${href}/`);
}

export function AccountNav() {
  const pathname = usePathname();

  async function onLogout() {
    await logoutCustomer();
    emitCartChanged();
    window.location.assign("/login");
  }

  return (
    <nav className="rounded-xl border border-slate-200 bg-white p-2 text-sm md:self-start">
      {links.map((link) => {
        const Icon = link.icon;
        const active = isActive(pathname, link.href);
        return (
          <Link
            key={link.href}
            href={link.href}
            className={cn(
              "flex min-h-11 items-center gap-2 rounded-lg px-3 py-2",
              active
                ? "bg-blue-50 font-semibold text-blue-700"
                : "text-slate-600 hover:bg-slate-50",
            )}
          >
            <Icon className="h-4 w-4" aria-hidden />
            {link.label}
          </Link>
        );
      })}
      <button
        type="button"
        className="mt-2 flex min-h-11 w-full items-center gap-2 border-t border-slate-100 px-3 py-2 text-left text-slate-500 hover:text-slate-950"
        onClick={() => void onLogout()}
      >
        <LogOut className="h-4 w-4" aria-hidden />
        Đăng xuất
      </button>
    </nav>
  );
}
