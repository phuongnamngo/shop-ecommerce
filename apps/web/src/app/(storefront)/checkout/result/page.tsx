import type { Metadata } from "next";

import { CheckoutResult } from "@/components/storefront/checkout-result";

export const metadata: Metadata = {
  title: "Kết quả đơn hàng",
  robots: { index: false, follow: false },
};

export default async function CheckoutResultPage({
  searchParams,
}: {
  searchParams: Promise<{ token?: string; number?: string; status?: string }>;
}) {
  const params = await searchParams;
  return (
    <CheckoutResult
      token={params.token}
      number={params.number}
      status={params.status}
    />
  );
}
