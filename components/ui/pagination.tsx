import Link from "next/link";
import { ChevronLeft, ChevronRight } from "lucide-react";
import { cn } from "@/lib/cn";

export function Pagination({
  page,
  totalPages,
  basePath,
  searchParams,
}: {
  page: number;
  totalPages: number;
  basePath: string;
  searchParams: Record<string, string | undefined>;
}) {
  if (totalPages <= 1) return null;

  function hrefFor(p: number) {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(searchParams)) {
      if (value) params.set(key, value);
    }
    params.set("page", String(p));
    return `${basePath}?${params.toString()}`;
  }

  const pages = Array.from({ length: totalPages }, (_, i) => i + 1).filter(
    (p) => p === 1 || p === totalPages || Math.abs(p - page) <= 1
  );

  return (
    <nav className="flex items-center justify-center gap-1 pt-4">
      <Link
        href={hrefFor(Math.max(1, page - 1))}
        aria-disabled={page === 1}
        className={cn(
          "flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium",
          page === 1 ? "pointer-events-none text-slate-300" : "text-slate-600 hover:bg-slate-100"
        )}
      >
        <ChevronLeft className="h-4 w-4" />
        Sebelumnya
      </Link>
      {pages.map((p, idx) => (
        <span key={p} className="flex items-center">
          {idx > 0 && pages[idx - 1] !== p - 1 && <span className="px-1 text-slate-400">…</span>}
          <Link
            href={hrefFor(p)}
            className={cn(
              "min-w-8 rounded-md px-3 py-1.5 text-center text-sm font-medium",
              p === page ? "bg-indigo-600 text-white shadow-sm shadow-indigo-600/20" : "text-slate-600 hover:bg-slate-100"
            )}
          >
            {p}
          </Link>
        </span>
      ))}
      <Link
        href={hrefFor(Math.min(totalPages, page + 1))}
        aria-disabled={page === totalPages}
        className={cn(
          "flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium",
          page === totalPages ? "pointer-events-none text-slate-300" : "text-slate-600 hover:bg-slate-100"
        )}
      >
        Selanjutnya
        <ChevronRight className="h-4 w-4" />
      </Link>
    </nav>
  );
}
