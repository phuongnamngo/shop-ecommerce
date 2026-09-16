"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { Menu, Search, ShoppingBag, Heart, UserRound, X, Bell } from "lucide-react";
import { useCallback, useEffect, useState, type ReactNode } from "react";

import { MiniCartDrawer } from "@/components/storefront/mini-cart-drawer";
import { StorefrontSearchField } from "@/components/storefront/storefront-search-field";
import { Button } from "@/components/ui/button";
import {
  Sheet,
  SheetContent,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { fetchActiveCart } from "@/lib/api/storefront/cart";
import { fetchCustomerMeOrNull } from "@/lib/api/storefront/customer";
import {
  listCustomerNotifications,
  markCustomerNotificationRead,
} from "@/lib/api/storefront/notifications";
import type { CustomerNotification } from "@/lib/api/storefront/types";
import { fetchWishlist } from "@/lib/api/storefront/wishlist";
import { CART_CHANGED_EVENT } from "@/lib/storefront/cart-events";
import { emitNotificationChanged, NOTIFICATION_CHANGED_EVENT } from "@/lib/storefront/notification-events";
import { WISHLIST_CHANGED_EVENT } from "@/lib/storefront/wishlist-events";
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

function useWishlistCount(): number {
  const [count, setCount] = useState(0);

  const refresh = useCallback(async () => {
    await Promise.resolve();
    try {
      const me = await fetchCustomerMeOrNull();
      if (!me) {
        setCount(0);
        return;
      }
      const wishlist = await fetchWishlist();
      setCount(wishlist.items.length);
    } catch {
      setCount(0);
    }
  }, []);

  useEffect(() => {
    const onChange = () => {
      void refresh();
    };
    const frame = requestAnimationFrame(onChange);
    window.addEventListener(WISHLIST_CHANGED_EVENT, onChange);
    return () => {
      cancelAnimationFrame(frame);
      window.removeEventListener(WISHLIST_CHANGED_EVENT, onChange);
    };
  }, [refresh]);

  return count;
}

function useInboxPreview(enabled: boolean): {
  unread: number;
  items: CustomerNotification[];
} {
  const [unread, setUnread] = useState(0);
  const [items, setItems] = useState<CustomerNotification[]>([]);

  const refresh = useCallback(async () => {
    await Promise.resolve();
    if (!enabled) {
      setUnread(0);
      setItems([]);
      return;
    }
    try {
      const res = await listCustomerNotifications(1, 5);
      setItems(res.data);
      setUnread(res.meta.unread_count);
    } catch {
      setUnread(0);
      setItems([]);
    }
  }, [enabled]);

  useEffect(() => {
    const onChange = () => {
      void refresh();
    };
    const frame = requestAnimationFrame(onChange);
    window.addEventListener(NOTIFICATION_CHANGED_EVENT, onChange);
    return () => {
      cancelAnimationFrame(frame);
      window.removeEventListener(NOTIFICATION_CHANGED_EVENT, onChange);
    };
  }, [refresh]);

  return { unread, items };
}

export function StorefrontHeader({
  categories,
  storeName,
}: {
  categories: NavCategory[];
  storeName: string;
}) {
  const cartQty = useCartQty();
  const wishlistCount = useWishlistCount();
  const [accountHref, setAccountHref] = useState("/login");
  const loggedIn = accountHref === "/account";
  const inbox = useInboxPreview(loggedIn);
  const [bellOpen, setBellOpen] = useState(false);
  const router = useRouter();
  const [searchOpen, setSearchOpen] = useState(false);
  const [cartOpen, setCartOpen] = useState(false);
  const nav = categories.slice(0, 6);
  const wishlistHref =
    accountHref === "/account"
      ? "/account/wishlist"
      : `/login?next=${encodeURIComponent("/account/wishlist")}`;

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
            <SheetTitle className="text-slate-900">{storeName}</SheetTitle>
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
                href={wishlistHref}
                className="rounded-lg px-3 py-3 hover:bg-slate-50"
              >
                Wishlist
                {wishlistCount > 0 ? ` (${wishlistCount})` : ""}
              </Link>
              {loggedIn ? (
                <Link
                  href="/account/notifications"
                  className="rounded-lg px-3 py-3 hover:bg-slate-50"
                >
                  Thông báo
                  {inbox.unread > 0 ? ` (${inbox.unread})` : ""}
                </Link>
              ) : null}
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
          {storeName}
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
          <StorefrontSearchField />
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
          {loggedIn ? (
            <>
              <Button variant="ghost" size="icon" asChild className="md:hidden">
                <Link
                  href="/account/notifications"
                  aria-label={`Thông báo${inbox.unread > 0 ? `, ${inbox.unread} chưa đọc` : ""}`}
                  className="relative"
                >
                  <Bell />
                  {inbox.unread > 0 ? (
                    <span className="absolute right-1 top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                      {inbox.unread > 99 ? "99+" : inbox.unread}
                    </span>
                  ) : null}
                </Link>
              </Button>
              <div className="relative hidden md:block">
                <Button
                  variant="ghost"
                  size="icon"
                  className="relative"
                  aria-label={`Thông báo${inbox.unread > 0 ? `, ${inbox.unread} chưa đọc` : ""}`}
                  aria-expanded={bellOpen}
                  onClick={() => setBellOpen((v) => !v)}
                >
                  <Bell />
                  {inbox.unread > 0 ? (
                    <span className="absolute right-1 top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                      {inbox.unread > 99 ? "99+" : inbox.unread}
                    </span>
                  ) : null}
                </Button>
                {bellOpen ? (
                  <div className="absolute right-0 top-full z-50 mt-1 w-80 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                    {inbox.items.length === 0 ? (
                      <p className="px-3 py-6 text-center text-sm text-slate-500">
                        Chưa có thông báo.
                      </p>
                    ) : (
                      <ul className="max-h-80 overflow-y-auto">
                        {inbox.items.map((item) => (
                          <li key={item.id}>
                            <button
                              type="button"
                              className="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50"
                              onClick={() => {
                                void markCustomerNotificationRead(item.id)
                                  .then(() => {
                                    emitNotificationChanged();
                                    setBellOpen(false);
                                    router.push(`/account/orders/${item.order_id}`);
                                  })
                                  .catch(() => {
                                    router.push(`/account/orders/${item.order_id}`);
                                  });
                              }}
                            >
                              <span className={item.read_at ? "text-slate-600" : "font-semibold text-slate-950"}>
                                {item.title}
                              </span>
                            </button>
                          </li>
                        ))}
                      </ul>
                    )}
                    <Link
                      href="/account/notifications"
                      className="mt-1 block rounded-lg px-3 py-2 text-center text-sm font-medium text-blue-700 hover:bg-blue-50"
                      onClick={() => setBellOpen(false)}
                    >
                      Xem tất cả
                    </Link>
                  </div>
                ) : null}
              </div>
            </>
          ) : null}
          <Button variant="ghost" size="icon" asChild>
            <Link
              href={wishlistHref}
              aria-label={`Wishlist${wishlistCount > 0 ? `, ${wishlistCount} sản phẩm` : ""}`}
              className="relative"
            >
              <Heart />
              {wishlistCount > 0 ? (
                <span className="absolute right-1 top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                  {wishlistCount > 99 ? "99+" : wishlistCount}
                </span>
              ) : null}
            </Link>
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
          <StorefrontSearchField autoFocus placeholder="Tìm sản phẩm" />
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
