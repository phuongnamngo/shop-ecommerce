"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Menu } from "lucide-react";
import { useState } from "react";

import {
  ADMIN_NAV_ITEMS,
  type AdminNavItem,
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
import { cn } from "@/lib/utils";

function NavItemLink({
  item,
  onNavigate,
  nested = false,
}: {
  item: AdminNavItem;
  onNavigate?: () => void;
  nested?: boolean;
}) {
  const pathname = usePathname();
  const active = isNavActive(pathname, item.href);

  return (
    <div className="flex flex-col gap-0.5">
      <Link
        href={item.children?.[0]?.href ?? item.href}
        onClick={onNavigate}
        className={cn(
          "rounded-md px-3 py-2 text-sm transition-colors",
          nested && "pl-5 text-[13px]",
          active
            ? "bg-sidebar-accent text-sidebar-foreground"
            : "text-sidebar-muted hover:bg-sidebar-accent/70 hover:text-sidebar-foreground",
        )}
      >
        {item.label}
      </Link>
      {item.children?.map((child) => (
        <NavItemLink
          key={child.href}
          item={child}
          onNavigate={onNavigate}
          nested
        />
      ))}
    </div>
  );
}

function NavLinks({ onNavigate }: { onNavigate?: () => void }) {
  return (
    <nav className="flex flex-col gap-1">
      {ADMIN_NAV_ITEMS.map((item) => (
        <NavItemLink key={item.href} item={item} onNavigate={onNavigate} />
      ))}
    </nav>
  );
}

export function AdminSidebar() {
  return (
    <aside className="hidden w-56 shrink-0 flex-col bg-sidebar text-sidebar-foreground md:flex">
      <div className="border-b border-white/10 px-4 py-4 text-sm font-semibold tracking-wide">
        Watch Admin
      </div>
      <div className="flex-1 overflow-y-auto p-3">
        <NavLinks />
      </div>
    </aside>
  );
}

export function AdminMobileNav() {
  const [open, setOpen] = useState(false);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button
          variant="outline"
          size="icon"
          className="md:hidden"
          aria-label="Open menu"
        >
          <Menu />
        </Button>
      </SheetTrigger>
      <SheetContent side="left">
        <SheetHeader>
          <SheetTitle>Watch Admin</SheetTitle>
        </SheetHeader>
        <NavLinks onNavigate={() => setOpen(false)} />
      </SheetContent>
    </Sheet>
  );
}
