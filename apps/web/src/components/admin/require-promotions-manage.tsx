"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

import { useAdminMe } from "@/hooks/use-admin-me";
import { canManagePromotions } from "@/lib/admin/can-manage-promotions";

export function RequirePromotionsManage({
  children,
  redirectTo,
}: {
  children: React.ReactNode;
  redirectTo: string;
}) {
  const router = useRouter();
  const me = useAdminMe();

  const allowed =
    me.isSuccess && me.data ? canManagePromotions(me.data.roles) : false;

  useEffect(() => {
    if (me.isPending) {
      return;
    }
    if (!me.isSuccess || !me.data || !canManagePromotions(me.data.roles)) {
      router.replace(redirectTo);
    }
  }, [me.isPending, me.isSuccess, me.data, redirectTo, router]);

  if (me.isPending || !allowed) {
    return (
      <div className="text-sm text-muted-foreground">Đang kiểm tra quyền…</div>
    );
  }

  return <>{children}</>;
}
