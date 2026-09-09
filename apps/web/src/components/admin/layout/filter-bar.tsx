import type { ReactNode } from "react";

import { cn } from "@/lib/utils";

export function FilterBar({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "flex flex-wrap items-center gap-2 border-b border-[#e2e8f0] bg-white px-4 py-3",
        className,
      )}
    >
      {children}
    </div>
  );
}
