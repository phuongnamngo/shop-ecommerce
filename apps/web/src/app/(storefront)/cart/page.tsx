import type { Metadata } from "next";

import { CartPage } from "@/components/storefront/cart-page";

export const metadata: Metadata = {
  title: "Giỏ hàng",
  robots: { index: false, follow: false },
};

export default function CartRoutePage() {
  return <CartPage />;
}
