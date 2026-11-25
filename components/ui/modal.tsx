"use client";

import { useEffect, useRef, type ReactNode } from "react";
import { X } from "lucide-react";

export function Modal({
  open,
  onClose,
  title,
  children,
}: {
  open: boolean;
  onClose: () => void;
  title: string;
  children: ReactNode;
}) {
  const ref = useRef<HTMLDialogElement>(null);

  useEffect(() => {
    const dialog = ref.current;
    if (!dialog) return;
    if (open && !dialog.open) dialog.showModal();
    if (!open && dialog.open) dialog.close();
  }, [open]);

  return (
    <dialog
      ref={ref}
      onClose={onClose}
      onCancel={onClose}
      className="fixed top-1/2 left-1/2 m-0 w-full max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-xl p-0 shadow-2xl shadow-slate-900/20 backdrop:bg-slate-900/50"
    >
      <div className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <h2 className="text-base font-semibold text-slate-900">{title}</h2>
        <button
          type="button"
          onClick={onClose}
          className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
          aria-label="Tutup"
        >
          <X className="h-4 w-4" />
        </button>
      </div>
      <div className="max-h-[75vh] overflow-y-auto px-5 py-4">{children}</div>
    </dialog>
  );
}
