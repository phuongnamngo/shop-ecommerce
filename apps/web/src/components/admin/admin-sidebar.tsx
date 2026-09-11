"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";
import { ChevronDown, LogOut, Menu, Store } from "lucide-react";
import { useState } from "react";

import {
  ADMIN_NAV_ITEMS,
  isNavActive,
} from "@/components/admin/admin-nav";
import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { adminLogout } from "@/lib/api/admin-auth";
import { adminMeQueryKey } from "@/lib/admin/query-keys";
import { cn } from "@/lib/utils";

function BrandMark() {
  return (
    <div className="mb-6 flex items-center gap-3 px-3 py-2">
      <div className="flex size-10 items-center justify-center rounded-xl bg-[#1f53c9] text-white shadow-sm shadow-[#1f53c9]/30">
        <Store className="size-5" />
      </div>
      <div>
        <p className="text-[15px] font-bold tracking-tight text-[#0f172a]">
          Atelier Commerce
        </p>
        <p className="text-[11px] font-medium text-[#64748b]">
          Retail Management
        </p>
      </div>
    </div>
  );
}

function NavLinks({
  onNavigate,
  onLogout,
}: {
  onNavigate?: () => void;
  onLogout: () => void;
}) {
  const pathname = usePathname();
  const catalogOpen = pathname.startsWith("/admin/catalog");
  const promotionsOpen = pathname.startsWith("/admin/promotions");

  return (
    <div className="flex h-full flex-col">
      <div className="p-4">
        <BrandMark />
        <p className="mb-2 block px-3 text-[11px] font-bold tracking-wider text-[#64748b] uppercase">
          Menu
        </p>
        <nav className="space-y-1">
          {ADMIN_NAV_ITEMS.map((item) => {
            const active = isNavActive(pathname, item.href);
            const Icon = item.icon;
            const sectionOpen =
              (item.href.startsWith("/admin/catalog") && catalogOpen) ||
              (item.href.startsWith("/admin/promotions") && promotionsOpen);
            const expanded = Boolean(item.children && sectionOpen);
            return (
              <div key={item.href} className="space-y-1">
                <Link
                  href={item.children?.[0]?.href ?? item.href}
                  onClick={onNavigate}
                  className={cn(
                    "flex items-center gap-3 rounded-xl px-4 py-2.5 text-[13px] transition-all duration-150 ease-out active:scale-[0.99]",
                    active && !item.children
                      ? "bg-[#ecf2ff] font-semibold text-[#1f53c9]"
                      : "font-medium text-[#334155] hover:bg-[#f8fafc] hover:text-[#0f172a]",
                    item.children && sectionOpen
                      ? "bg-[#ecf2ff] font-semibold text-[#1f53c9]"
                      : null,
                  )}
                >
                  <Icon
                    className={cn(
                      "size-[18px]",
                      active || expanded ? "text-[#1f53c9]" : "text-[#64748b]",
                    )}
                  />
                  <span className="flex-1">{item.label}</span>
                  {item.children ? (
                    <ChevronDown
                      className={cn(
                        "size-4 text-[#64748b] transition-transform duration-200",
                        expanded && "rotate-180",
                      )}
                    />
                  ) : null}
                </Link>
                {item.children ? (
                  <div
                    className={cn(
                      "grid transition-all duration-200 ease-out",
                      expanded
                        ? "grid-rows-[1fr] opacity-100"
                        : "grid-rows-[0fr] opacity-0",
                    )}
                  >
                    <div className="overflow-hidden">
                      <div className="space-y-1 pr-2 pl-9">
                        {item.children.map((child) => (
                          <Link
                            key={child.href}
                            href={child.href}
                            onClick={onNavigate}
                            className={cn(
                              "flex items-center rounded-lg px-3 py-1.5 text-[13px] transition-colors duration-150",
                              isNavActive(pathname, child.href)
                                ? "bg-[#1f53c9] font-semibold text-white"
                                : "text-[#64748b] hover:text-[#1f53c9]",
                            )}
                          >
                            {child.label}
                          </Link>
                        ))}
                      </div>
                    </div>
                  </div>
                ) : null}
              </div>
            );
          })}
        </nav>
      </div>
      <div className="mt-auto border-t border-[#e2e8f0] p-4">
        <button
          type="button"
          onClick={onLogout}
          className="flex h-10 w-full items-center gap-3 rounded-xl px-4 text-[13px] font-medium text-[#64748b] hover:bg-[#f8fafc] hover:text-[#0f172a]"
        >
          <LogOut className="size-4" />
          Log Out
        </button>
      </div>
    </div>
  );
}

export function AdminSidebar() {
  const router = useRouter();
  const queryClient = useQueryClient();

  async function handleLogout() {
    try {
      await adminLogout();
    } finally {
      queryClient.removeQueries({ queryKey: adminMeQueryKey });
      router.replace("/admin/login");
    }
  }

  return (
    <aside className="fixed top-0 left-0 z-50 hidden h-screen w-[260px] flex-col overflow-y-auto border-r border-[#e2e8f0] bg-white md:flex">
      <NavLinks onLogout={() => void handleLogout()} />
    </aside>
  );
}

export function AdminMobileNav() {
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const queryClient = useQueryClient();

  async function handleLogout() {
    try {
      await adminLogout();
    } finally {
      queryClient.removeQueries({ queryKey: adminMeQueryKey });
      setOpen(false);
      router.replace("/admin/login");
    }
  }

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button
          variant="outline"
          size="icon"
          className="h-10 w-10 md:hidden"
          aria-label="Open menu"
        >
          <Menu className="size-4" />
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-[260px] bg-white p-0">
        <SheetHeader className="sr-only">
          <SheetTitle>Atelier Commerce</SheetTitle>
        </SheetHeader>
        <NavLinks
          onNavigate={() => setOpen(false)}
          onLogout={() => void handleLogout()}
        />
      </SheetContent>
    </Sheet>
  );
}
