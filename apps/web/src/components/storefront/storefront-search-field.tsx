"use client";

import Link from "next/link";
import { Search } from "lucide-react";
import {
  useEffect,
  useId,
  useMemo,
  useRef,
  useState,
  type KeyboardEvent,
  type ReactNode,
} from "react";

import { Input } from "@/components/ui/input";
import { storefrontMediaUrl } from "@/lib/api/storefront/browser";
import {
  suggestCatalog,
  type CatalogSuggest,
} from "@/lib/api/storefront/suggest";
import { listingHref } from "@/lib/storefront/listing";
import { cn } from "@/lib/utils";

type SuggestItem =
  | { kind: "product"; href: string; label: string; hint?: string }
  | { kind: "category"; href: string; label: string }
  | { kind: "brand"; href: string; label: string };

function flattenSuggest(data: CatalogSuggest | null): SuggestItem[] {
  if (!data) return [];
  return [
    ...data.products.map((item) => ({
      kind: "product" as const,
      href: `/products/${item.slug}`,
      label: item.name,
      hint: item.thumbnail_url ?? undefined,
    })),
    ...data.categories.map((item) => ({
      kind: "category" as const,
      href: listingHref({ category: item.slug }),
      label: item.name,
    })),
    ...data.brands.map((item) => ({
      kind: "brand" as const,
      href: listingHref({ brand: item.slug }),
      label: item.name,
    })),
  ];
}

export function StorefrontSearchField({
  autoFocus = false,
  placeholder = "Tìm áo thun, polo, jean...",
  className,
}: {
  autoFocus?: boolean;
  placeholder?: string;
  className?: string;
}) {
  const listId = useId();
  const rootRef = useRef<HTMLDivElement>(null);
  const [query, setQuery] = useState("");
  const [open, setOpen] = useState(false);
  const [active, setActive] = useState(-1);
  const [fetched, setFetched] = useState<{
    q: string;
    data: CatalogSuggest;
  } | null>(null);
  const trimmed = query.trim();
  const data =
    trimmed.length < 2 || fetched?.q !== trimmed ? null : fetched.data;
  const items = useMemo(() => flattenSuggest(data), [data]);
  const listOpen = open && items.length > 0;

  useEffect(() => {
    if (trimmed.length < 2) {
      return;
    }
    const handle = window.setTimeout(() => {
      void suggestCatalog(trimmed)
        .then((result) => {
          setFetched({ q: trimmed, data: result });
          const empty =
            result.products.length +
              result.categories.length +
              result.brands.length ===
            0;
          setOpen(!empty);
          setActive(-1);
        })
        .catch(() => {
          setFetched(null);
          setOpen(false);
          setActive(-1);
        });
    }, 200);
    return () => window.clearTimeout(handle);
  }, [trimmed]);

  useEffect(() => {
    const onPointerDown = (event: PointerEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("pointerdown", onPointerDown);
    return () => document.removeEventListener("pointerdown", onPointerDown);
  }, []);

  const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
    if (event.key === "Escape") {
      setOpen(false);
      setActive(-1);
      return;
    }
    if (!listOpen) return;
    if (event.key === "ArrowDown") {
      event.preventDefault();
      setActive((current) => (current + 1) % items.length);
    } else if (event.key === "ArrowUp") {
      event.preventDefault();
      setActive((current) => (current <= 0 ? items.length - 1 : current - 1));
    } else if (event.key === "Enter" && active >= 0) {
      const item = items[active];
      if (item) {
        event.preventDefault();
        window.location.assign(item.href);
      }
    }
  };

  return (
    <div ref={rootRef} className={cn("relative", className)}>
      <label className="relative block">
        <span className="sr-only">Tìm sản phẩm</span>
        <Search
          className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
          aria-hidden
        />
        <Input
          type="search"
          name="q"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          onFocus={() => {
            if (items.length > 0) setOpen(true);
          }}
          onKeyDown={onKeyDown}
          autoFocus={autoFocus}
          placeholder={placeholder}
          autoComplete="off"
          role="combobox"
          aria-expanded={listOpen}
          aria-controls={listId}
          aria-autocomplete="list"
          className="h-11 rounded-full border-slate-200 bg-slate-50 pl-10"
        />
      </label>
      {listOpen ? (
        <div
          id={listId}
          role="listbox"
          className="absolute z-50 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white py-2 shadow-lg"
        >
          {data?.products.length ? (
            <SuggestGroup label="Sản phẩm">
              {data.products.map((item, index) => (
                <SuggestLink
                  key={`p-${item.id}`}
                  href={`/products/${item.slug}`}
                  active={active === index}
                  thumbnail={item.thumbnail_url}
                  onSelect={() => setOpen(false)}
                >
                  {item.name}
                </SuggestLink>
              ))}
            </SuggestGroup>
          ) : null}
          {data?.categories.length ? (
            <SuggestGroup label="Danh mục">
              {data.categories.map((item, index) => (
                <SuggestLink
                  key={`c-${item.id}`}
                  href={listingHref({ category: item.slug })}
                  active={
                    active ===
                    (data?.products.length ?? 0) + index
                  }
                  onSelect={() => setOpen(false)}
                >
                  {item.name}
                </SuggestLink>
              ))}
            </SuggestGroup>
          ) : null}
          {data?.brands.length ? (
            <SuggestGroup label="Thương hiệu">
              {data.brands.map((item, index) => (
                <SuggestLink
                  key={`b-${item.id}`}
                  href={listingHref({ brand: item.slug })}
                  active={
                    active ===
                    (data?.products.length ?? 0) +
                      (data?.categories.length ?? 0) +
                      index
                  }
                  onSelect={() => setOpen(false)}
                >
                  {item.name}
                </SuggestLink>
              ))}
            </SuggestGroup>
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

function SuggestGroup({
  label,
  children,
}: {
  label: string;
  children: ReactNode;
}) {
  return (
    <div className="px-2 py-1">
      <p className="px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
        {label}
      </p>
      <ul>{children}</ul>
    </div>
  );
}

function SuggestLink({
  href,
  active,
  thumbnail,
  onSelect,
  children,
}: {
  href: string;
  active: boolean;
  thumbnail?: string | null;
  onSelect?: () => void;
  children: ReactNode;
}) {
  const src = storefrontMediaUrl(thumbnail);
  return (
    <li>
      <Link
        href={href}
        role="option"
        aria-selected={active}
        onClick={onSelect}
        className={cn(
          "flex items-center gap-2 rounded-lg px-2 py-2 text-sm hover:bg-slate-50",
          active && "bg-slate-100",
        )}
      >
        {src ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={src} alt="" className="h-8 w-8 rounded object-cover" />
        ) : null}
        <span>{children}</span>
      </Link>
    </li>
  );
}
