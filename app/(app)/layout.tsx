import type { ReactNode } from "react";
import { requireUser } from "@/lib/rbac";
import { Sidebar } from "@/components/sidebar";
import { Topbar } from "@/components/topbar";

export default async function AppLayout({ children }: { children: ReactNode }) {
  const user = await requireUser();

  return (
    <div className="flex min-h-screen bg-slate-50">
      <Sidebar role={user.role} />
      <div className="flex flex-1 flex-col lg:pl-64">
        <Topbar name={user.name ?? user.username} role={user.role} />
        <main className="flex-1 p-4 lg:p-8">{children}</main>
      </div>
    </div>
  );
}
