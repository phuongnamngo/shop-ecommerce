"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

import { logoutCustomer } from "@/lib/api/storefront/customer";
import { emitCartChanged } from "@/lib/storefront/cart-events";

const links = [
  { href: "/account", label: "Hồ sơ" },
  { href: "/account/addresses", label: "Địa chỉ" },
  { href: "/account/orders", label: "Đơn hàng" },
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
    <nav className="flex flex-col gap-1 rounded-lg border bg-white p-2 text-sm md:self-start">
      {links.map((link) => (
        <Link
          key={link.href}
          href={link.href}
          className={
            isActive(pathname, link.href)
              ? "rounded-md bg-zinc-950 px-3 py-2 font-medium text-white"
              : "rounded-md px-3 py-2 text-zinc-600 hover:bg-zinc-100"
          }
        >
          {link.label}
        </Link>
      ))}
      <button
        type="button"
        className="mt-2 border-t px-3 py-2 text-left text-zinc-500 hover:text-zinc-950"
        onClick={() => void onLogout()}
      >
        Đăng xuất
      </button>
    </nav>
  );
}
