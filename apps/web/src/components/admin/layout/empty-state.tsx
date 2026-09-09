import type { ReactNode } from "react";

import { Inbox } from "lucide-react";

import { cn } from "@/lib/utils";

export function EmptyState({
  title,
  description,
  action,
  className,
}: {
  title: string;
  description?: string;
  action?: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "flex flex-col items-center justify-center px-4 py-12 text-center",
        className,
      )}
    >
      <div className="mb-3 flex size-12 items-center justify-center rounded-xl bg-[#ecf2ff] text-[#1f53c9]">
        <Inbox className="size-5" />
      </div>
      <p className="text-sm font-semibold text-[#0f172a]">{title}</p>
      {description ? (
        <p className="mt-1 max-w-sm text-sm text-[#64748b]">{description}</p>
      ) : null}
      {action ? <div className="mt-4">{action}</div> : null}
    </div>
  );
}

export function LoadingState({
  label = "Loading…",
  className,
}: {
  label?: string;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "flex items-center justify-center gap-2 px-4 py-10 text-sm text-[#64748b]",
        className,
      )}
    >
      <span className="size-4 animate-spin rounded-full border-2 border-[#e2e8f0] border-t-[#1f53c9]" />
      {label}
    </div>
  );
}
