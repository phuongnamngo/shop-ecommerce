export function discountPercent(
  price: string | number | null | undefined,
  compareAt: string | number | null | undefined,
): number | null {
  if (price == null || compareAt == null) return null;
  const p = Number(price);
  const c = Number(compareAt);
  if (!Number.isFinite(p) || !Number.isFinite(c) || c <= p) return null;
  return Math.max(1, Math.round((1 - p / c) * 100));
}
