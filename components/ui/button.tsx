import { cn } from "@/lib/cn";
import type { ButtonHTMLAttributes } from "react";

type Variant = "primary" | "secondary" | "danger" | "ghost" | "outline";
type Size = "sm" | "md";

const variants: Record<Variant, string> = {
  primary:
    "bg-indigo-600 text-white shadow-sm shadow-indigo-600/20 hover:bg-indigo-500 disabled:bg-indigo-300 disabled:shadow-none",
  secondary: "bg-slate-100 text-slate-700 hover:bg-slate-200 disabled:text-slate-400",
  outline: "border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 disabled:text-slate-400",
  danger: "bg-red-600 text-white shadow-sm shadow-red-600/20 hover:bg-red-500 disabled:bg-red-300",
  ghost: "bg-transparent text-slate-600 hover:bg-slate-100 disabled:text-slate-300",
};

const sizes: Record<Size, string> = {
  sm: "px-3 py-1.5 text-xs",
  md: "px-4 py-2 text-sm",
};

export function Button({
  variant = "primary",
  size = "md",
  className,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement> & { variant?: Variant; size?: Size }) {
  return (
    <button
      className={cn(
        "inline-flex items-center justify-center gap-1.5 rounded-lg font-medium transition-colors duration-150",
        "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2",
        "disabled:cursor-not-allowed",
        sizes[size],
        variants[variant],
        className
      )}
      {...props}
    />
  );
}
