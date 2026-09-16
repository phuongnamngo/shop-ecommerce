"use client";

import type { ReactNode } from "react";
import { usePathname } from "next/navigation";

import { StorefrontBottomNav } from "@/components/storefront/storefront-bottom-nav";
import { StorefrontFooter } from "@/components/storefront/storefront-footer";
import { StorefrontHeader } from "@/components/storefront/storefront-header";
import { StorefrontPromoBar } from "@/components/storefront/storefront-promo-bar";
import type {
  PublicCmsBanner,
  PublicCmsPageListItem,
} from "@/lib/api/storefront/cms";
import { StoreNameProvider } from "@/lib/storefront/store-name-context";

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
  promo,
  pages,
  storeName,
  children,
}: {
  categories: NavCategory[];
  promo: PublicCmsBanner | null;
  pages: PublicCmsPageListItem[];
  storeName: string;
  children: ReactNode;
}) {
  const pathname = usePathname();
  const auth = isAuthPath(pathname);
  const compactBottom = hideBottomNav(pathname);

  const inner = auth ? (
    <div className="flex min-h-full flex-col">{children}</div>
  ) : (
    <>
      <StorefrontPromoBar promo={promo} />
      <StorefrontHeader categories={categories} storeName={storeName} />
      <div className={compactBottom ? "flex-1" : "flex-1 pb-20 md:pb-0"}>
        {children}
      </div>
      <StorefrontFooter pages={pages} storeName={storeName} />
      {compactBottom ? null : <StorefrontBottomNav />}
    </>
  );

  return <StoreNameProvider storeName={storeName}>{inner}</StoreNameProvider>;
}
