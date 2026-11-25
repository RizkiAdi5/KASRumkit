import { Hospital } from "lucide-react";
import { LoginForm } from "./login-form";

export default function LoginPage() {
  return (
    <div className="relative flex min-h-dvh items-center justify-center overflow-hidden bg-slate-950 px-4">
      <div className="pointer-events-none absolute inset-0">
        <div className="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl" />
        <div className="absolute -right-40 -bottom-40 h-96 w-96 rounded-full bg-indigo-500/10 blur-3xl" />
      </div>

      <div className="relative w-full max-w-sm">
        <div className="mb-6 flex flex-col items-center text-center">
          <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-400 ring-1 ring-indigo-500/20">
            <Hospital className="h-6 w-6" strokeWidth={2} />
          </div>
          <h1 className="text-lg font-semibold text-white">KAS Ruang Gelatik</h1>
          <p className="text-sm text-slate-400">Sistem Pengaturan Kas Rumah Sakit</p>
        </div>

        <div className="rounded-2xl border border-white/10 bg-white p-8 shadow-2xl shadow-black/40">
          <LoginForm />
        </div>

        <p className="mt-6 text-center text-xs text-slate-500">&copy; {new Date().getFullYear()} KAS Ruang Gelatik</p>
      </div>
    </div>
  );
}
