"use client";

import Link from "next/link";
import { Menu, Search, ShoppingBag, UserRound, X } from "lucide-react";
import { useCallback, useEffect, useState, type ReactNode } from "react";

import { MiniCartDrawer } from "@/components/storefront/mini-cart-drawer";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Sheet,
  SheetContent,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { fetchActiveCart } from "@/lib/api/storefront/cart";
import { fetchCustomerMeOrNull } from "@/lib/api/storefront/customer";
import { CART_CHANGED_EVENT } from "@/lib/storefront/cart-events";
import { STORE_NAME } from "@/lib/storefront/ui";
import { cn } from "@/lib/utils";

type NavCategory = { name: string; slug: string };

function useCartQty(): number {
  const [qty, setQty] = useState(0);

  const refresh = useCallback(async () => {
    await Promise.resolve();
    try {
      const cart = await fetchActiveCart();
      setQty(cart.items.reduce((sum, item) => sum + item.qty, 0));
    } catch {
      setQty(0);
    }
  }, []);

  useEffect(() => {
    const onChange = () => {
      void refresh();
    };
    const frame = requestAnimationFrame(onChange);
    window.addEventListener(CART_CHANGED_EVENT, onChange);
    window.addEventListener("storage", onChange);
    return () => {
      cancelAnimationFrame(frame);
      window.removeEventListener(CART_CHANGED_EVENT, onChange);
      window.removeEventListener("storage", onChange);
    };
  }, [refresh]);

  return qty;
}

export function StorefrontHeader({
  categories,
}: {
  categories: NavCategory[];
}) {
  const cartQty = useCartQty();
  const [accountHref, setAccountHref] = useState("/login");
  const [searchOpen, setSearchOpen] = useState(false);
  const [cartOpen, setCartOpen] = useState(false);
  const nav = categories.slice(0, 6);

  useEffect(() => {
    const frame = requestAnimationFrame(() => {
      void fetchCustomerMeOrNull()
        .then((me) => setAccountHref(me ? "/account" : "/login"))
        .catch(() => setAccountHref("/login"));
    });
    return () => cancelAnimationFrame(frame);
  }, []);

  return (
    <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06)] backdrop-blur">
      <div className="mx-auto flex max-w-[1320px] items-center gap-3 px-4 py-3 sm:px-6">
        <Sheet>
          <SheetTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              className="text-slate-900 hover:bg-slate-100 md:hidden"
              aria-label="Mở menu"
            >
              <Menu />
            </Button>
          </SheetTrigger>
          <SheetContent
            side="left"
            className="w-80 bg-white text-slate-900"
          >
            <SheetTitle className="text-slate-900">{STORE_NAME}</SheetTitle>
            <nav className="flex flex-col gap-1 pt-4 text-sm">
              <Link
                href="/products"
                className="rounded-lg px-3 py-3 font-medium hover:bg-slate-50"
              >
                Cửa hàng
              </Link>
              {nav.map((c) => (
                <Link
                  key={c.slug}
                  href={`/products?category=${encodeURIComponent(c.slug)}`}
                  className="rounded-lg px-3 py-3 hover:bg-slate-50"
                >
                  {c.name}
                </Link>
              ))}
              <Link
                href="/cart"
                className="rounded-lg px-3 py-3 hover:bg-slate-50"
              >
                Giỏ hàng
                {cartQty > 0 ? ` (${cartQty})` : ""}
              </Link>
              <Link
                href={accountHref}
                className="rounded-lg px-3 py-3 hover:bg-slate-50"
              >
                Tài khoản
              </Link>
            </nav>
          </SheetContent>
        </Sheet>

        <Link
          href="/"
          className="text-lg font-bold tracking-[0.18em] text-slate-950"
        >
          {STORE_NAME}
        </Link>

        <nav className="hidden items-center gap-1 text-sm font-medium md:flex">
          <HeaderNavLink href="/products">Cửa hàng</HeaderNavLink>
          {nav.map((c) => (
            <HeaderNavLink
              key={c.slug}
              href={`/products?category=${encodeURIComponent(c.slug)}`}
            >
              {c.name}
            </HeaderNavLink>
          ))}
        </nav>

        <form
          action="/products"
          method="get"
          className="ml-auto hidden max-w-sm flex-1 lg:block"
        >
          <label className="relative block">
            <span className="sr-only">Tìm sản phẩm</span>
            <Search
              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              aria-hidden
            />
            <Input
              type="search"
              name="q"
              placeholder="Tìm sản phẩm, đồng hồ..."
              className="h-11 rounded-full border-slate-200 bg-slate-50 pl-10"
            />
          </label>
        </form>

        <div className="ml-auto flex items-center gap-1 lg:ml-0">
          <Button
            variant="ghost"
            size="icon"
            className="lg:hidden"
            aria-label="Tìm kiếm"
            onClick={() => setSearchOpen((v) => !v)}
          >
            {searchOpen ? <X /> : <Search />}
          </Button>
          <Button variant="ghost" size="icon" asChild>
            <Link href={accountHref} aria-label="Tài khoản">
              <UserRound />
            </Link>
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="relative"
            aria-label={`Giỏ hàng${cartQty > 0 ? `, ${cartQty} sản phẩm` : ""}`}
            onClick={() => setCartOpen(true)}
          >
            <ShoppingBag />
            {cartQty > 0 ? (
              <span className="absolute right-1 top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                {cartQty > 99 ? "99+" : cartQty}
              </span>
            ) : null}
          </Button>
        </div>
      </div>
      {searchOpen ? (
        <form
          action="/products"
          method="get"
          className="border-t border-slate-200 px-4 py-3 lg:hidden"
        >
          <label className="relative block">
            <span className="sr-only">Tìm sản phẩm</span>
            <Search
              className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              aria-hidden
            />
            <Input
              type="search"
              name="q"
              autoFocus
              placeholder="Tìm sản phẩm"
              className="h-11 rounded-full border-slate-200 bg-slate-50 pl-10"
            />
          </label>
        </form>
      ) : null}
      <MiniCartDrawer open={cartOpen} onOpenChange={setCartOpen} />
    </header>
  );
}

function HeaderNavLink({
  href,
  children,
}: {
  href: string;
  children: ReactNode;
}) {
  return (
    <Link
      href={href}
      className={cn(
        "relative px-3 py-2 text-slate-700 after:absolute after:inset-x-3 after:bottom-1 after:h-0.5 after:origin-left after:scale-x-0 after:bg-blue-600 after:transition-transform hover:text-slate-950 hover:after:scale-x-100",
      )}
    >
      {children}
    </Link>
  );
}
