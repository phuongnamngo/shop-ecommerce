import { Suspense } from "react";

import { PaymentsPage } from "@/components/admin/payments/payments-page";
import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

export default function AdminPaymentsRoute() {
  return (
    <Suspense
      fallback={
        <Card>
          <CardHeader>
            <CardTitle>Payments</CardTitle>
            <CardDescription>Đang tải…</CardDescription>
          </CardHeader>
        </Card>
      }
    >
      <PaymentsPage />
    </Suspense>
  );
}
