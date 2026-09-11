export function safeInternalPath(next: string | null | undefined): string | null {
  if (!next) return null;
  if (next.startsWith("/") && !next.startsWith("//") && !next.includes("://")) {
    return next;
  }
  return null;
}
