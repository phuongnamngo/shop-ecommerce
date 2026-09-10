import Link from "next/link";

import { sfContainer } from "@/lib/storefront/ui";

export default function StorefrontNotFound() {
  return (
    <main className={`${sfContainer} py-16`}>
      <h1 className="text-3xl font-bold tracking-tight">Không tìm thấy trang</h1>
      <p className="mt-2 text-slate-500">
        Đường dẫn không tồn tại hoặc sản phẩm không còn hiển thị.
      </p>
      <p className="mt-6">
        <Link href="/" className="font-semibold text-blue-600">
          Về trang chủ
        </Link>
      </p>
    </main>
  );
}
