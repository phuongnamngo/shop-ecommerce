export type AdminSetting = {
  key: string;
  group: string | null;
  value: { vi?: string; code?: string } | Record<string, unknown> | null;
};
