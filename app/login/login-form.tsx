"use client";

import { useActionState } from "react";
import { AlertCircle, Lock, User } from "lucide-react";
import { loginAction } from "./actions";

export function LoginForm() {
  const [error, formAction, isPending] = useActionState(loginAction, undefined);

  return (
    <form action={formAction} className="space-y-4">
      <div>
        <label htmlFor="username" className="mb-1.5 block text-sm font-medium text-slate-700">
          Username
        </label>
        <div className="relative">
          <User className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            id="username"
            name="username"
            type="text"
            required
            autoFocus
            autoComplete="username"
            className="w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
          />
        </div>
      </div>
      <div>
        <label htmlFor="password" className="mb-1.5 block text-sm font-medium text-slate-700">
          Password
        </label>
        <div className="relative">
          <Lock className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            id="password"
            name="password"
            type="password"
            required
            autoComplete="current-password"
            className="w-full rounded-lg border border-slate-300 py-2 pr-3 pl-9 text-sm outline-none transition-colors focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20"
          />
        </div>
      </div>
      {error && (
        <p className="flex items-center gap-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
          <AlertCircle className="h-4 w-4 shrink-0" />
          {error}
        </p>
      )}
      <button
        type="submit"
        disabled={isPending}
        className="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition-colors hover:bg-indigo-500 disabled:opacity-60"
      >
        {isPending ? "Memproses..." : "Masuk"}
      </button>
    </form>
  );
}
