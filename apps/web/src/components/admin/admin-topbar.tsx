"use client";

import { useRouter } from "next/navigation";
import { useQueryClient } from "@tanstack/react-query";

import { AdminMobileNav } from "@/components/admin/admin-sidebar";
import { Button } from "@/components/ui/button";
import { adminLogout } from "@/lib/api/admin-auth";
import type { AdminProfile } from "@/lib/api/types";
import { adminMeQueryKey } from "@/lib/admin/query-keys";

export function AdminTopbar({ user }: { user: AdminProfile }) {
  const router = useRouter();
  const queryClient = useQueryClient();

  async function handleLogout() {
    try {
      await adminLogout();
    } finally {
      queryClient.removeQueries({ queryKey: adminMeQueryKey });
      router.replace("/admin/login");
    }
  }

  return (
    <header className="flex h-14 items-center gap-3 border-b bg-background px-4">
      <AdminMobileNav />
      <div className="ml-auto flex items-center gap-3">
        <div className="hidden text-right text-sm sm:block">
          <div className="font-medium text-foreground">{user.name}</div>
          <div className="text-xs text-muted-foreground">{user.email}</div>
        </div>
        <Button type="button" variant="outline" size="sm" onClick={handleLogout}>
          Logout
        </Button>
      </div>
    </header>
  );
}
