"use client";

import type { ReactNode } from "react";
import { usePathname } from "next/navigation";

import { StorefrontBottomNav } from "@/components/storefront/storefront-bottom-nav";
import { StorefrontFooter } from "@/components/storefront/storefront-footer";
import { StorefrontHeader } from "@/components/storefront/storefront-header";
import { StorefrontPromoBar } from "@/components/storefront/storefront-promo-bar";

type NavCategory = { name: string; slug: string };

const AUTH_PREFIXES = [
  "/login",
  "/register",
  "/forgot-password",
  "/reset-password",
];

function isAuthPath(pathname: string): boolean {
  return AUTH_PREFIXES.some((p) => pathname === p || pathname.startsWith(`${p}/`));
}

function hideBottomNav(pathname: string): boolean {
  if (isAuthPath(pathname)) return true;
  if (pathname === "/cart" || pathname.startsWith("/checkout")) return true;
  if (/^\/products\/[^/]+$/.test(pathname)) return true;
  return false;
}

export function StorefrontChrome({
  categories,
  children,
}: {
  categories: NavCategory[];
  children: ReactNode;
}) {
  const pathname = usePathname();
  const auth = isAuthPath(pathname);
  const compactBottom = hideBottomNav(pathname);

  if (auth) {
    return <div className="flex min-h-full flex-col">{children}</div>;
  }

  return (
    <>
      <StorefrontPromoBar />
      <StorefrontHeader categories={categories} />
      <div className={compactBottom ? "flex-1" : "flex-1 pb-20 md:pb-0"}>
        {children}
      </div>
      <StorefrontFooter />
      {compactBottom ? null : <StorefrontBottomNav />}
    </>
  );
}
