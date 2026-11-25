import { cn } from "@/lib/cn";
import type { ReactNode } from "react";

export function Badge({
  children,
  tone = "slate",
}: {
  children: ReactNode;
  tone?: "slate" | "green" | "red" | "blue";
}) {
  const tones: Record<string, string> = {
    slate: "bg-slate-100 text-slate-600 ring-slate-500/10",
    green: "bg-emerald-50 text-emerald-700 ring-emerald-600/15",
    red: "bg-red-50 text-red-700 ring-red-600/15",
    blue: "bg-indigo-50 text-indigo-700 ring-indigo-600/15",
  };
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset",
        tones[tone]
      )}
    >
      {children}
    </span>
  );
}
