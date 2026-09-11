import type { LucideIcon } from "lucide-react";
import {
  LayoutDashboard,
  Package,
  ShoppingCart,
  Tag,
  Warehouse,
  Users,
} from "lucide-react";

export type AdminNavItem = {
  href: string;
  label: string;
  icon: LucideIcon;
  children?: Array<{ href: string; label: string }>;
};

export const ADMIN_NAV_ITEMS: AdminNavItem[] = [
  { href: "/admin", label: "Dashboard", icon: LayoutDashboard },
  {
    href: "/admin/catalog/products",
    label: "Products",
    icon: Package,
    children: [
      { href: "/admin/catalog/products", label: "Product List" },
      { href: "/admin/catalog/categories", label: "Categories" },
      { href: "/admin/catalog/brands", label: "Brands" },
      { href: "/admin/catalog/reviews", label: "Reviews" },
    ],
  },
  { href: "/admin/orders", label: "Orders", icon: ShoppingCart },
  { href: "/admin/customers", label: "Customers", icon: Users },
  { href: "/admin/inventory", label: "Inventory", icon: Warehouse },
  {
    href: "/admin/promotions",
    label: "Khuyến mãi",
    icon: Tag,
    children: [
      { href: "/admin/promotions/discounts", label: "Discounts" },
      { href: "/admin/promotions/coupons", label: "Coupons" },
      { href: "/admin/promotions/flash-sales", label: "Flash sales" },
    ],
  },
];

export function isNavActive(pathname: string, href: string): boolean {
  if (href === "/admin") {
    return pathname === "/admin";
  }
  return pathname === href || pathname.startsWith(`${href}/`);
}

export function adminBreadcrumb(pathname: string): {
  parent: string;
  current: string;
} {
  if (pathname.startsWith("/admin/customers/")) {
    return { parent: "Customers", current: "Customer 360" };
  }
  if (pathname.startsWith("/admin/orders/")) {
    return { parent: "Orders", current: "Order" };
  }
  if (pathname.startsWith("/admin/catalog/reviews")) {
    return { parent: "Catalog", current: "Reviews" };
  }
  if (pathname.startsWith("/admin/catalog/categories")) {
    return { parent: "Catalog", current: "Categories" };
  }
  if (pathname.startsWith("/admin/catalog/brands")) {
    return { parent: "Catalog", current: "Brands" };
  }
  if (pathname.startsWith("/admin/catalog/products")) {
    return { parent: "Catalog", current: "Products" };
  }
  if (pathname.startsWith("/admin/promotions/flash-sales")) {
    return { parent: "Khuyến mãi", current: "Flash sales" };
  }
  if (pathname.startsWith("/admin/promotions/coupons")) {
    return { parent: "Khuyến mãi", current: "Coupons" };
  }
  if (pathname.startsWith("/admin/promotions/discounts")) {
    return { parent: "Khuyến mãi", current: "Discounts" };
  }
  const match = ADMIN_NAV_ITEMS.find(
    (item) => item.href !== "/admin" && isNavActive(pathname, item.href),
  );
  if (!match) {
    return { parent: "Overview", current: "Dashboard" };
  }
  return { parent: "Overview", current: match.label };
}
