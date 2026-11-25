"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import type { Role } from "@prisma/client";
import {
  Hospital,
  LayoutDashboard,
  ArrowLeftRight,
  BarChart3,
  Tags,
  Briefcase,
  UserCog,
  Menu,
  X,
  Store,
  type LucideIcon,
} from "lucide-react";
import { cn } from "@/lib/cn";

const navItems: { href: string; label: string; icon: LucideIcon; roles: Role[] }[] = [
  { href: "/dashboard", label: "Dashboard", icon: LayoutDashboard, roles: ["ADMIN", "SUPERVISOR", "KASIR"] },
  { href: "/transaksi", label: "Transaksi", icon: ArrowLeftRight, roles: ["ADMIN", "SUPERVISOR", "KASIR"] },
  { href: "/laporan", label: "Laporan", icon: BarChart3, roles: ["ADMIN", "SUPERVISOR"] },
  { href: "/kategori", label: "Kategori", icon: Tags, roles: ["ADMIN", "SUPERVISOR"] },
  { href: "/pegawai", label: "Pegawai", icon: Briefcase, roles: ["ADMIN", "SUPERVISOR"] },
  { href: "/koperasi", label: "Koperasi", icon: Store, roles: ["ADMIN", "SUPERVISOR", "KASIR"] },
  { href: "/users", label: "Pengguna", icon: UserCog, roles: ["ADMIN"] },
];

export function Sidebar({ role }: { role: Role }) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);
  const items = navItems.filter((item) => item.roles.includes(role));

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="fixed top-3 left-3 z-30 rounded-lg bg-slate-950 p-2 text-white shadow-lg lg:hidden"
        aria-label="Buka menu"
      >
        <Menu className="h-5 w-5" />
      </button>
      {open && (
        <div
          className="fixed inset-0 z-30 bg-slate-950/40 backdrop-blur-[1px] lg:hidden"
          onClick={() => setOpen(false)}
        />
      )}
      <aside
        className={cn(
          "fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col bg-slate-950 text-slate-100 transition-transform lg:translate-x-0",
          open ? "translate-x-0" : "-translate-x-full"
        )}
      >
        <div className="flex items-center justify-between gap-2 border-b border-white/10 px-5 py-5">
          <div className="flex items-center gap-2.5">
            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-400">
              <Hospital className="h-5 w-5" strokeWidth={2} />
            </span>
            <div className="min-w-0">
              <p className="truncate text-sm font-semibold leading-tight text-white">KAS Ruang Gelatik</p>
              <p className="truncate text-xs text-slate-400">Sistem Kas Rumah Sakit</p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => setOpen(false)}
            className="rounded-md p-1 text-slate-400 hover:bg-white/10 lg:hidden"
            aria-label="Tutup menu"
          >
            <X className="h-4 w-4" />
          </button>
        </div>
        <nav className="flex-1 space-y-0.5 p-3">
          <p className="px-3 pt-2 pb-1 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Menu</p>
          {items.map((item) => {
            const active = pathname === item.href || pathname.startsWith(`${item.href}/`);
            const Icon = item.icon;
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setOpen(false)}
                className={cn(
                  "group relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors",
                  active ? "bg-white/10 text-white" : "text-slate-400 hover:bg-white/5 hover:text-white"
                )}
              >
                {active && <span className="absolute top-1/2 left-0 h-5 w-0.5 -translate-y-1/2 rounded-full bg-indigo-400" />}
                <Icon className={cn("h-[18px] w-[18px]", active ? "text-indigo-400" : "text-slate-500 group-hover:text-slate-300")} strokeWidth={2} />
                {item.label}
              </Link>
            );
          })}
        </nav>
      </aside>
    </>
  );
}
