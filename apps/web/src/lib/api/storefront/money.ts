const vnd = new Intl.NumberFormat("vi-VN", {
  maximumFractionDigits: 0,
});

export function formatVnd(value: string | number): string {
  const n = typeof value === "number" ? value : Number(value);
  if (!Number.isFinite(n)) return `0₫`;
  return `${vnd.format(n)}₫`;
}
