import Link from "next/link";

import { Button } from "@/components/ui/button";

export function EmptyState({
  title,
  description,
  actionHref,
  actionLabel,
}: {
  title: string;
  description?: string;
  actionHref?: string;
  actionLabel?: string;
}) {
  return (
    <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-16 text-center">
      <h2 className="text-lg font-semibold text-slate-900">{title}</h2>
      {description ? (
        <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">
          {description}
        </p>
      ) : null}
      {actionHref && actionLabel ? (
        <Button
          asChild
          className="mt-6 h-11 rounded-lg bg-blue-600 hover:bg-blue-700"
        >
          <Link href={actionHref}>{actionLabel}</Link>
        </Button>
      ) : null}
    </div>
  );
}
