"use client";

import * as React from "react";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { cn } from "@/lib/utils";

export function ModalForm({
  open,
  onOpenChange,
  title,
  description,
  children,
  footer,
  className,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  className?: string;
}) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent
        className={cn(
          "max-h-[90vh] gap-0 overflow-hidden p-0 sm:max-w-2xl sm:rounded-xl",
          className,
        )}
      >
        <DialogHeader className="border-b border-[#e2e8f0] px-6 py-4">
          <DialogTitle className="text-[16px] font-semibold tracking-tight text-[#0f172a]">
            {title}
          </DialogTitle>
          {description ? (
            <DialogDescription className="text-[12px] text-[#64748b]">
              {description}
            </DialogDescription>
          ) : null}
        </DialogHeader>
        <div className="max-h-[min(70vh,640px)] overflow-y-auto px-6 py-5">
          {children}
        </div>
        {footer ? (
          <DialogFooter className="border-t border-[#e2e8f0] bg-[#f8fafc] px-6 py-3 sm:justify-between">
            {footer}
          </DialogFooter>
        ) : null}
      </DialogContent>
    </Dialog>
  );
}
