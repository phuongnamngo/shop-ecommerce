import type { ReactNode } from "react";

import { cn } from "@/lib/utils";

import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

export function DataTableShell({
  children,
  className,
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div
      className={cn(
        "overflow-hidden rounded-xl border border-[#e2e8f0] bg-white shadow-[0_4px_20px_rgba(15,23,42,0.04)]",
        className,
      )}
    >
      {children}
    </div>
  );
}

export function AdminPagination({
  page,
  lastPage,
  total,
  onPrev,
  onNext,
}: {
  page: number;
  lastPage: number;
  total: number;
  onPrev: () => void;
  onNext: () => void;
}) {
  return (
    <div className="flex items-center justify-between border-t border-[#e2e8f0] bg-[#f8fafc] px-4 py-3 text-[13px] text-[#64748b]">
      <span className="tabular-nums">
        Page {page}/{lastPage} · {total} records
      </span>
      <div className="flex gap-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8 rounded-lg"
          disabled={page <= 1}
          onClick={onPrev}
        >
          Prev
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="h-8 rounded-lg"
          disabled={page >= lastPage}
          onClick={onNext}
        >
          Next
        </Button>
      </div>
    </div>
  );
}

export {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
};
