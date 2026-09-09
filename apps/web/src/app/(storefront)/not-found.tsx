import Link from "next/link";

export default function StorefrontNotFound() {
  return (
    <main className="mx-auto max-w-6xl px-4 py-16">
      <h1 className="text-2xl font-semibold">Không tìm thấy trang</h1>
      <p className="mt-2 text-zinc-600">
        Đường dẫn không tồn tại hoặc sản phẩm không còn hiển thị.
      </p>
      <p className="mt-6">
        <Link href="/" className="underline">
          Về trang chủ
        </Link>
      </p>
    </main>
  );
}
