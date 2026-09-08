"use client";

import { useQuery } from "@tanstack/react-query";

import { adminMe } from "@/lib/api/admin-auth";
import { adminMeQueryKey } from "@/lib/admin/query-keys";

export function useAdminMe(options?: { enabled?: boolean }) {
  return useQuery({
    queryKey: adminMeQueryKey,
    queryFn: async () => {
      const res = await adminMe();
      return res.data;
    },
    retry: false,
    enabled: options?.enabled ?? true,
  });
}
