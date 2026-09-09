"use client";

import Link from "next/link";
import { Menu } from "lucide-react";
import { useCallback, useEffect, useState } from "react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Sheet,
  SheetContent,
  SheetTitle,
  SheetTrigger,
} from "@/components/ui/sheet";
import { fetchCart, getCartToken } from "@/lib/api/storefront/cart";
import { CART_CHANGED_EVENT } from "@/lib/storefront/cart-events";

type NavCategory = { name: string; slug: string };

function useCartQty(): number {
  const [qty, setQty] = useState(0);

  const refresh = useCallback(async () => {
    await Promise.resolve();
    if (!getCartToken()) {
      setQty(0);
      return;
    }
    try {
      const cart = await fetchCart();
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

function QtyBadge({ qty }: { qty: number }) {
  return (
    <span className="ml-1 inline-flex min-w-5 items-center justify-center rounded-full bg-zinc-50 px-1.5 text-xs font-medium text-zinc-950">
      {qty}
    </span>
  );
}

export function StorefrontHeader({
  categories,
}: {
  categories: NavCategory[];
}) {
  const cartQty = useCartQty();
  return (
    <header className="border-b bg-zinc-950 text-zinc-50">
      <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
        <Sheet>
          <SheetTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              className="text-zinc-50 hover:bg-zinc-800 md:hidden"
              aria-label="Mở menu"
            >
              <Menu />
            </Button>
          </SheetTrigger>
          <SheetContent
            side="left"
            className="bg-zinc-950 text-zinc-50"
          >
            <SheetTitle className="sr-only">Menu</SheetTitle>
            <nav className="flex flex-col gap-3 pt-8 text-sm">
              <Link href="/products" className="hover:underline">
                Cửa hàng
              </Link>
              {categories.map((c) => (
                <Link
                  key={c.slug}
                  href={`/products?category=${encodeURIComponent(c.slug)}`}
                  className="hover:underline"
                >
                  {c.name}
                </Link>
              ))}
              <Link href="/cart" className="hover:underline">
                Giỏ hàng
                <QtyBadge qty={cartQty} />
              </Link>
              <Link href="/account" className="hover:underline">
                Tài khoản
              </Link>
            </nav>
          </SheetContent>
        </Sheet>

        <Link href="/" className="text-lg font-semibold tracking-wide">
          Watch
        </Link>

        <nav className="hidden items-center gap-4 text-sm md:flex">
          <Link href="/products" className="hover:text-zinc-300">
            Cửa hàng
          </Link>
          {categories.map((c) => (
            <Link
              key={c.slug}
              href={`/products?category=${encodeURIComponent(c.slug)}`}
              className="hover:text-zinc-300"
            >
              {c.name}
            </Link>
          ))}
        </nav>

        <form
          action="/products"
          method="get"
          className="ml-auto hidden max-w-xs flex-1 sm:block"
        >
          <Input
            type="search"
            name="q"
            placeholder="Tìm sản phẩm"
            className="border-zinc-700 bg-zinc-900 text-zinc-50 placeholder:text-zinc-500"
          />
        </form>

        <div className="ml-auto flex items-center gap-2 sm:ml-0">
          <Button
            variant="ghost"
            className="text-zinc-50 hover:bg-zinc-800"
            asChild
          >
            <Link href="/cart">
              Giỏ
              <QtyBadge qty={cartQty} />
            </Link>
          </Button>
          <Button
            variant="ghost"
            className="text-zinc-50 hover:bg-zinc-800"
            asChild
          >
            <Link href="/account">Tài khoản</Link>
          </Button>
        </div>
      </div>
      <form
        action="/products"
        method="get"
        className="border-t border-zinc-800 px-4 py-2 sm:hidden"
      >
        <Input
          type="search"
          name="q"
          placeholder="Tìm sản phẩm"
          className="border-zinc-700 bg-zinc-900 text-zinc-50 placeholder:text-zinc-500"
        />
      </form>
    </header>
  );
}
