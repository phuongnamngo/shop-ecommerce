import { Suspense } from "react";

import { OrdersListPage } from "@/components/admin/orders/orders-list-page";
import {
  Card,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

export default function AdminOrdersPage() {
  return (
    <Suspense
      fallback={
        <Card>
          <CardHeader>
            <CardTitle>Orders</CardTitle>
            <CardDescription>Đang tải…</CardDescription>
          </CardHeader>
        </Card>
      }
    >
      <OrdersListPage />
    </Suspense>
  );
}
