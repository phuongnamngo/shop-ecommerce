import Link from "next/link";

import { STORE_NAME } from "@/lib/storefront/ui";

export function StorefrontFooter() {
  const year = new Date().getFullYear();
  return (
    <footer className="mt-auto border-t border-slate-200 bg-white">
      <div className="mx-auto grid max-w-[1320px] gap-10 px-4 py-12 sm:px-6 md:grid-cols-4">
        <div>
          <p className="text-lg font-bold tracking-[0.18em]">{STORE_NAME}</p>
          <p className="mt-3 max-w-xs text-sm leading-relaxed text-slate-500">
            Thời trang nam tối giản, dễ mặc mỗi ngày. Giá, tồn kho và vận chuyển
            luôn lấy từ hệ thống.
          </p>
        </div>
        <div>
          <p className="text-sm font-semibold">Cửa hàng</p>
          <nav className="mt-3 flex flex-col gap-2 text-sm text-slate-600">
            <Link href="/products" className="hover:text-slate-950">
              Tất cả sản phẩm
            </Link>
            <Link href="/products?sort=newest" className="hover:text-slate-950">
              Hàng mới
            </Link>
            <Link href="/cart" className="hover:text-slate-950">
              Giỏ hàng
            </Link>
          </nav>
        </div>
        <div>
          <p className="text-sm font-semibold">Hỗ trợ</p>
          <nav className="mt-3 flex flex-col gap-2 text-sm text-slate-600">
            <Link href="/account" className="hover:text-slate-950">
              Tài khoản
            </Link>
            <Link href="/account/orders" className="hover:text-slate-950">
              Tra cứu đơn hàng
            </Link>
            <Link href="/login" className="hover:text-slate-950">
              Đăng nhập
            </Link>
          </nav>
        </div>
        <div>
          <p className="text-sm font-semibold">Về chúng tôi</p>
          <p className="mt-3 text-sm leading-relaxed text-slate-500">
            Giao hàng toàn quốc · Đổi trả 30 ngày · Thanh toán COD và VNPay.
          </p>
        </div>
      </div>
      <div className="border-t border-slate-200">
        <div className="mx-auto flex max-w-[1320px] flex-col gap-2 px-4 py-4 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <p>
            © {year} {STORE_NAME}. Tất cả các quyền được bảo lưu.
          </p>
          <p>COD · VNPay</p>
        </div>
      </div>
    </footer>
  );
}
