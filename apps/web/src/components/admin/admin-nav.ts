export type AdminNavItem = {
  href: string;
  label: string;
  children?: AdminNavItem[];
};

export const ADMIN_NAV_ITEMS: AdminNavItem[] = [
  { href: "/admin", label: "Dashboard" },
  {
    href: "/admin/catalog",
    label: "Catalog",
    children: [
      { href: "/admin/catalog/products", label: "Products" },
      { href: "/admin/catalog/brands", label: "Brands" },
      { href: "/admin/catalog/categories", label: "Categories" },
    ],
  },
  { href: "/admin/orders", label: "Orders" },
  { href: "/admin/customers", label: "Customers" },
  { href: "/admin/inventory", label: "Inventory" },
];

export function isNavActive(pathname: string, href: string): boolean {
  if (href === "/admin") {
    return pathname === "/admin";
  }
  return pathname === href || pathname.startsWith(`${href}/`);
}
