"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";

import { AdminSidebar } from "@/components/admin/admin-sidebar";
import { AdminTopbar } from "@/components/admin/admin-topbar";
import { Button } from "@/components/ui/button";
import { useAdminMe } from "@/hooks/use-admin-me";
import { adminLogout } from "@/lib/api/admin-auth";
import { ApiError } from "@/lib/api/client";
import { adminMeQueryKey } from "@/lib/admin/query-keys";

export default function AdminProtectedLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useAdminMe();

  useEffect(() => {
    if (!me.isError) {
      return;
    }

    const status = me.error instanceof ApiError ? me.error.status : undefined;

    if (status === 401) {
      router.replace("/admin/login");
      return;
    }

    if (status === 403) {
      void (async () => {
        try {
          await adminLogout();
        } finally {
          queryClient.removeQueries({ queryKey: adminMeQueryKey });
          router.replace("/admin/login?error=forbidden");
        }
      })();
    }
  }, [me.isError, me.error, queryClient, router]);

  if (me.isPending) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-muted/30 text-sm text-muted-foreground">
        Đang tải phiên Admin…
      </div>
    );
  }

  if (me.isError) {
    const status = me.error instanceof ApiError ? me.error.status : undefined;

    if (status === 401 || status === 403) {
      return (
        <div className="flex min-h-screen items-center justify-center bg-muted/30 text-sm text-muted-foreground">
          Đang chuyển hướng…
        </div>
      );
    }

    return (
      <div className="flex min-h-screen flex-col items-center justify-center gap-3 bg-muted/30 px-4 text-center">
        <p className="text-sm text-muted-foreground">
          Không tải được hồ sơ Admin. Kiểm tra API / mạng rồi thử lại.
        </p>
        <Button type="button" onClick={() => void me.refetch()}>
          Retry
        </Button>
      </div>
    );
  }

  if (!me.data) {
    return null;
  }

  return (
    <div className="flex min-h-screen bg-muted/20">
      <AdminSidebar />
      <div className="flex min-w-0 flex-1 flex-col">
        <AdminTopbar user={me.data} />
        <main className="flex-1 p-4 md:p-6">{children}</main>
      </div>
    </div>
  );
}
