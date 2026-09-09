import type { ReactNode } from "react";

import { cn } from "@/lib/utils";

export function ChartCard({
  title,
  subtitle,
  badge,
  actions,
  children,
  className,
}: {
  title: string;
  subtitle?: string;
  badge?: ReactNode;
  actions?: ReactNode;
  children: ReactNode;
  className?: string;
}) {
  return (
    <section
      className={cn(
        "flex flex-col rounded-xl border border-[#e2e8f0] bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,0.04)]",
        className,
      )}
    >
      <div className="flex flex-col gap-3 border-b border-[#e2e8f0] pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-2">
            <h3 className="text-lg font-bold tracking-tight text-[#0f172a]">
              {title}
            </h3>
            {badge}
          </div>
          {subtitle ? (
            <p className="mt-0.5 text-sm text-[#64748b]">{subtitle}</p>
          ) : null}
        </div>
        {actions}
      </div>
      <div className="pt-4">{children}</div>
    </section>
  );
}
