import { Minus, Plus } from "lucide-react";

import { cn } from "@/lib/utils";

export function QuantitySelector({
  value,
  onChange,
  disabled,
  min = 1,
  max = 99,
  id,
}: {
  value: number;
  onChange: (next: number) => void;
  disabled?: boolean;
  min?: number;
  max?: number;
  id?: string;
}) {
  return (
    <div
      className={cn(
        "inline-flex h-11 items-center rounded-lg border border-slate-200 bg-white",
        disabled && "opacity-50",
      )}
    >
      <button
        type="button"
        aria-label="Giảm số lượng"
        className="flex h-11 w-11 items-center justify-center text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        disabled={disabled || value <= min}
        onClick={() => onChange(Math.max(min, value - 1))}
      >
        <Minus className="h-4 w-4" />
      </button>
      <span id={id} className="min-w-8 text-center text-sm font-semibold">
        {value}
      </span>
      <button
        type="button"
        aria-label="Tăng số lượng"
        className="flex h-11 w-11 items-center justify-center text-slate-700 hover:bg-slate-50 disabled:opacity-40"
        disabled={disabled || value >= max}
        onClick={() => onChange(Math.min(max, value + 1))}
      >
        <Plus className="h-4 w-4" />
      </button>
    </div>
  );
}
