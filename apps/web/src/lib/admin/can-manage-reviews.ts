export function canManageReviews(roles: string[]): boolean {
  return roles.includes("admin") || roles.includes("super_admin");
}
