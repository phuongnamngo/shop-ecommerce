import Link from "next/link";

export function StorefrontPromoBar() {
  return (
    <div className="bg-slate-950 text-white">
      <div className="mx-auto flex max-w-[1320px] items-center justify-center gap-3 px-4 py-2 text-center text-xs sm:text-sm">
        <span>
          Miễn phí vận chuyển cho đơn từ 499.000₫ · Đổi trả trong 30 ngày
        </span>
        <Link
          href="/products"
          className="hidden font-semibold text-blue-300 underline-offset-2 hover:underline sm:inline"
        >
          Mua ngay
        </Link>
      </div>
    </div>
  );
}
