import { cn } from "@/lib/utils";

type Tone = "success" | "warning" | "danger" | "neutral" | "info";

const TONE_CLASS: Record<Tone, string> = {
  success: "bg-[#e6fffa] text-[#0f766e]",
  warning: "bg-[#fef5e5] text-[#b45309]",
  danger: "bg-[#fdede8] text-[#c2410c]",
  info: "bg-[#ecf2ff] text-[#1f53c9]",
  neutral: "bg-[#f1f5f9] text-[#475569]",
};

const DOT_CLASS: Record<Tone, string> = {
  success: "bg-[#13deb9]",
  warning: "bg-[#f59e0b]",
  danger: "bg-[#fa896b]",
  info: "bg-[#1f53c9]",
  neutral: "bg-[#64748b]",
};

function toneFor(status: string): Tone {
  const value = status.toLowerCase();
  if (
    ["active", "paid", "completed", "shipped", "operational", "in stock"].includes(
      value,
    )
  ) {
    return "success";
  }
  if (["pending", "fulfilling", "draft", "low", "low stock"].includes(value)) {
    return "warning";
  }
  if (
    ["cancelled", "banned", "inactive", "failed", "out", "out of stock"].includes(
      value,
    )
  ) {
    return "danger";
  }
  return "info";
}

export function StatusBadge({
  status,
  className,
}: {
  status: string;
  className?: string;
}) {
  const tone = toneFor(status);
  return (
    <span
      className={cn(
        "inline-flex h-[22px] items-center gap-1.5 rounded-full px-2.5 text-[11px] font-semibold tracking-wide capitalize",
        TONE_CLASS[tone],
        className,
      )}
    >
      <span className={cn("size-1.5 rounded-full", DOT_CLASS[tone])} />
      {status}
    </span>
  );
}
