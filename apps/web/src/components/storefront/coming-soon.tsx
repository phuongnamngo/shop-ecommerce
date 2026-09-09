import Link from "next/link";

export function ComingSoon({ title }: { title: string }) {
  return (
    <main className="mx-auto max-w-6xl px-4 py-16">
      <h1 className="text-2xl font-semibold">{title}</h1>
      <p className="mt-2 text-zinc-600">Sắp ra mắt.</p>
      <p className="mt-6">
        <Link href="/products" className="underline">
          Tiếp tục xem sản phẩm
        </Link>
      </p>
    </main>
  );
}
