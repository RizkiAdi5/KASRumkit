import { LogOut } from "lucide-react";
import { logoutAction } from "@/app/(app)/actions";

const roleLabel: Record<string, string> = {
  ADMIN: "Admin",
  SUPERVISOR: "Supervisor",
  KASIR: "Kasir",
};

export function Topbar({ name, role }: { name: string; role: string }) {
  const initials = name
    .split(" ")
    .map((w) => w[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();

  return (
    <header className="flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur-sm lg:px-8">
      <div className="w-8 lg:hidden" />
      <div className="hidden text-sm text-slate-400 lg:block">
        {new Date().toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" })}
      </div>
      <form action={logoutAction} className="flex items-center gap-3">
        <div className="flex items-center gap-2.5">
          <span className="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white">
            {initials}
          </span>
          <div className="hidden text-right sm:block">
            <p className="text-sm leading-tight font-medium text-slate-800">{name}</p>
            <p className="text-xs text-slate-400">{roleLabel[role] ?? role}</p>
          </div>
        </div>
        <button
          type="submit"
          className="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
        >
          <LogOut className="h-4 w-4" />
          <span className="hidden sm:inline">Keluar</span>
        </button>
      </form>
    </header>
  );
}
