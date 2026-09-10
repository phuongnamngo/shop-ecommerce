import Link from "next/link";

export type BreadcrumbItem = { href?: string; label: string };

export function StorefrontBreadcrumb({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav aria-label="Breadcrumb" className="text-sm text-slate-500">
      <ol className="flex flex-wrap items-center gap-1">
        {items.map((item, index) => {
          const last = index === items.length - 1;
          return (
            <li key={`${item.label}-${index}`} className="flex items-center gap-1">
              {index > 0 ? <span aria-hidden>/</span> : null}
              {last || !item.href ? (
                <span className={last ? "font-medium text-slate-900" : undefined}>
                  {item.label}
                </span>
              ) : (
                <Link href={item.href} className="hover:text-blue-600">
                  {item.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
}
