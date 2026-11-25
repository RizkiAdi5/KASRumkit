"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Trash2 } from "lucide-react";
import { deleteTransaksiAction } from "./actions";

export function DeleteButton({ id }: { id: number }) {
  const [pending, setPending] = useState(false);

  return (
    <button
      type="button"
      disabled={pending}
      onClick={async () => {
        if (!confirm("Hapus transaksi ini?")) return;
        setPending(true);
        try {
          await deleteTransaksiAction(id);
          toast.success("Transaksi dihapus.");
        } catch {
          toast.error("Gagal menghapus transaksi.");
        } finally {
          setPending(false);
        }
      }}
      className="inline-flex items-center gap-1 rounded-md p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
      aria-label="Hapus transaksi"
    >
      <Trash2 className="h-4 w-4" />
    </button>
  );
}
