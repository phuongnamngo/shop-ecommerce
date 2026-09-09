export function canManageOrders(roles: string[]): boolean {
  return roles.includes("admin") || roles.includes("super_admin");
}
