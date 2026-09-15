export function canManageCms(roles: string[]): boolean {
  return roles.includes("admin") || roles.includes("super_admin");
}
