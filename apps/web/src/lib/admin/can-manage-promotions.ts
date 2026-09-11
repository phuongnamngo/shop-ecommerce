export function canManagePromotions(roles: string[]): boolean {
  return roles.includes("admin") || roles.includes("super_admin");
}
