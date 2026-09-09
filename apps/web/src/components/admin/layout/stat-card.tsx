import type { ReactNode } from "react";

import { cn } from "@/lib/utils";

const ICON_TONES = {
  primary: "bg-[#ecf2ff] text-[#1f53c9]",
  secondary: "bg-[#e8f7ff] text-[#00658f]",
  success: "bg-[#e6fffa] text-[#13deb9]",
  warning: "bg-[#fef5e5] text-[#f59e0b]",
  danger: "bg-[#fdede8] text-[#fa896b]",
} as const;

export function StatCard({
  label,
  value,
  hint,
  icon,
  tone = "primary",
  footer,
  className,
}: {
  label: string;
  value: ReactNode;
  hint?: ReactNode;
  icon?: ReactNode;
  tone?: keyof typeof ICON_TONES;
  footer?: ReactNode;
  className?: string;
}) {
  return (
    <section
      className={cn(
        "flex flex-col justify-between rounded-xl border border-[#e2e8f0] bg-white p-5 shadow-[0_4px_20px_rgba(15,23,42,0.04)] transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgba(31,83,201,0.08)]",
        className,
      )}
    >
      <div className="mb-3 flex items-center justify-between">
        <span className="text-[11px] font-semibold tracking-wider text-[#64748b] uppercase">
          {label}
        </span>
        {icon ? (
          <div
            className={cn(
              "flex size-11 items-center justify-center rounded-xl",
              ICON_TONES[tone],
            )}
          >
            {icon}
          </div>
        ) : null}
      </div>
      <p className="text-[28px] leading-9 font-bold tracking-tight text-[#0f172a] tabular-nums">
        {value}
      </p>
      {hint ? (
        <div className="mt-2 text-xs leading-4 text-[#64748b]">{hint}</div>
      ) : null}
      {footer}
    </section>
  );
}
