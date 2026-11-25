import { cn } from "@/lib/cn";
import type { LucideIcon } from "lucide-react";

type Tone = "default" | "positive" | "negative";

const toneStyles: Record<Tone, { icon: string; value: string }> = {
  default: { icon: "bg-indigo-50 text-indigo-600", value: "text-slate-900" },
  positive: { icon: "bg-emerald-50 text-emerald-600", value: "text-emerald-600" },
  negative: { icon: "bg-red-50 text-red-600", value: "text-red-600" },
};

export function StatCard({
  label,
  value,
  tone = "default",
  icon: Icon,
}: {
  label: string;
  value: string;
  tone?: Tone;
  icon: LucideIcon;
}) {
  const t = toneStyles[tone];
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-900/[0.02]">
      <div className="flex items-start justify-between">
        <p className="text-sm font-medium text-slate-500">{label}</p>
        <span className={cn("flex h-9 w-9 shrink-0 items-center justify-center rounded-lg", t.icon)}>
          <Icon className="h-[18px] w-[18px]" strokeWidth={2} />
        </span>
      </div>
      <p className={cn("mt-3 text-2xl font-semibold tracking-tight", t.value)}>{value}</p>
    </div>
  );
}
