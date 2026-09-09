import Link from "next/link";

export function StorefrontFooter() {
  return (
    <footer className="mt-auto border-t bg-zinc-50">
      <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-zinc-600 sm:flex-row sm:items-center sm:justify-between">
        <p>© {new Date().getFullYear()} Watch</p>
        <nav className="flex gap-4">
          <Link href="/products" className="hover:text-zinc-950">
            Cửa hàng
          </Link>
          <Link href="/cart" className="hover:text-zinc-950">
            Giỏ hàng
          </Link>
          <Link href="/account" className="hover:text-zinc-950">
            Tài khoản
          </Link>
        </nav>
      </div>
    </footer>
  );
}
