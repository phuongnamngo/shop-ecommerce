import type { PageMeta } from "@/lib/api/catalog/types";

export type ActivityChange = { old: unknown; new: unknown };

export type AdminActivity = {
  id: number;
  log_name: string | null;
  event: string | null;
  description: string;
  subject_type: string | null;
  subject_id: number | null;
  causer_id: number | null;
  causer_name: string | null;
  changes: Record<string, ActivityChange>;
  created_at: string;
};

export type { PageMeta };
