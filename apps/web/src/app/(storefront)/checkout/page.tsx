import type { Metadata } from "next";

import { CheckoutForm } from "@/components/storefront/checkout-form";

export const metadata: Metadata = {
  title: "Thanh toán",
  robots: { index: false, follow: false },
};

export default function CheckoutPage() {
  return <CheckoutForm />;
}
