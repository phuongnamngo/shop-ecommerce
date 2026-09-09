"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Bell, Calendar, Moon, Search } from "lucide-react";

import { AdminMobileNav } from "@/components/admin/admin-sidebar";
import type { AdminProfile } from "@/lib/api/types";

function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "A";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
}

export function AdminTopbar({ user }: { user: AdminProfile }) {
  const pathname = usePathname();
  const role = user.roles[0] ?? "Admin";

  return (
    <header className="sticky top-0 z-40 flex h-[70px] items-center border-b border-[#e2e8f0] bg-white shadow-sm">
      <div className="flex h-full w-full items-center justify-between gap-4 px-4 md:px-8">
        <div className="flex min-w-0 flex-1 items-center gap-4">
          <AdminMobileNav />
          <label className="relative hidden w-full max-w-md items-center sm:flex">
            <Search className="pointer-events-none absolute left-3.5 size-[18px] text-[#64748b]" />
            <input
              className="h-10 w-full rounded-lg border border-[#e2e8f0] bg-[#f8fafc] py-2 pr-12 pl-10 text-[13px] text-[#0f172a] outline-none placeholder:text-[#64748b] focus:border-[#1f53c9] focus:ring-2 focus:ring-[#1f53c9]/20"
              placeholder="Search orders, products, inventory, customers…"
              type="search"
              aria-label="Search"
            />
            <kbd className="absolute top-2 right-3 rounded border border-[#e2e8f0] bg-white px-1.5 py-0.5 font-mono text-[10px] font-semibold text-[#64748b]">
              ⌘K
            </kbd>
          </label>
        </div>

        <nav className="hidden items-center gap-6 lg:flex">
          <Link
            href="/admin"
            className={
              pathname === "/admin"
                ? "text-[13px] font-semibold text-[#1f53c9]"
                : "text-[13px] font-medium text-[#64748b] hover:text-[#0f172a]"
            }
          >
            Dashboard
          </Link>
          <span className="text-[13px] font-medium text-[#64748b]">
            Analytics
          </span>
          <span className="text-[13px] font-medium text-[#64748b]">Reports</span>
        </nav>

        <div className="flex items-center gap-2">
          <button
            type="button"
            className="flex size-10 items-center justify-center rounded-lg text-[#64748b] hover:bg-[#f8fafc] hover:text-[#0f172a]"
            aria-label="Calendar"
          >
            <Calendar className="size-5" />
          </button>
          <button
            type="button"
            className="relative flex size-10 items-center justify-center rounded-lg text-[#64748b] hover:bg-[#f8fafc] hover:text-[#0f172a]"
            aria-label="Notifications"
          >
            <Bell className="size-5" />
            <span className="absolute top-2 right-2 size-2 rounded-full bg-[#fa896b] ring-2 ring-white" />
          </button>
          <button
            type="button"
            className="hidden size-10 items-center justify-center rounded-lg text-[#64748b] hover:bg-[#f8fafc] hover:text-[#0f172a] sm:flex"
            aria-label="Theme"
          >
            <Moon className="size-5" />
          </button>
          <div className="mx-1 hidden h-6 w-px bg-[#e2e8f0] sm:block" />
          <div className="flex items-center gap-3 pl-1">
            <div className="flex size-10 items-center justify-center rounded-full border-2 border-[#1f53c9]/20 bg-[#ecf2ff] text-[12px] font-bold text-[#1f53c9]">
              {initials(user.name)}
            </div>
            <div className="hidden text-left xl:block">
              <p className="text-[13px] font-semibold text-[#0f172a]">
                {user.name}
              </p>
              <p className="text-[11px] text-[#64748b] capitalize">
                {role.replaceAll("_", " ")}
              </p>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
}
