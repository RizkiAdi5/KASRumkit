"use client";

import { useActionState, useEffect, useState } from "react";
import { toast } from "sonner";
import type { KoperasiProduk } from "@prisma/client";
import { Plus, Pencil, Trash2, Package } from "lucide-react";
import { Modal } from "@/components/ui/modal";
import { Button } from "@/components/ui/button";
import { Input, Label } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardHeader } from "@/components/ui/card";
import { EmptyRow } from "@/components/ui/empty-state";
import { ErrorMessage } from "@/components/ui/error-message";
import { formatRupiah } from "@/lib/format";
import { saveProdukAction, toggleProdukActiveAction, deleteProdukAction, type ActionResult } from "./actions";

const initialState: ActionResult = { ok: false };

type ProdukRow = Omit<KoperasiProduk, "hargaJual"> & { hargaJual: number | null };

export function ProdukManager({ produkList }: { produkList: ProdukRow[] }) {
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<ProdukRow | null>(null);
  const [state, formAction, isPending] = useActionState(saveProdukAction, initialState);

  useEffect(() => {
    if (state.ok) {
      toast.success("Produk berhasil disimpan.");
      setOpen(false);
      setEditing(null);
    }
  }, [state]);

  function openCreate() {
    setEditing(null);
    setOpen(true);
  }

  function openEdit(p: ProdukRow) {
    setEditing(p);
    setOpen(true);
  }

  async function handleToggle(p: ProdukRow) {
    try {
      await toggleProdukActiveAction(p.id, !p.isActive);
      toast.success(p.isActive ? "Produk dinonaktifkan." : "Produk diaktifkan.");
    } catch {
      toast.error("Gagal mengubah status produk.");
    }
  }

  async function handleDelete(p: ProdukRow) {
    if (!confirm(`Hapus produk "${p.namaProduk}"?`)) return;
    try {
      await deleteProdukAction(p.id);
      toast.success("Produk dihapus.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Gagal menghapus produk.");
    }
  }

  return (
    <Card>
      <CardHeader className="flex items-center justify-between">
        <h2 className="font-semibold text-slate-900">Daftar Produk</h2>
        <Button size="sm" onClick={openCreate}>
          <Plus className="h-4 w-4" />
          Tambah Produk
        </Button>
      </CardHeader>
      <div className="overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
              <th className="px-5 py-3">Nama Produk</th>
              <th className="px-5 py-3 text-right">Harga Jual</th>
              <th className="px-5 py-3">Status</th>
              <th className="px-5 py-3" />
            </tr>
          </thead>
          <tbody>
            {produkList.map((p) => (
              <tr key={p.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                <td className="px-5 py-3 font-medium text-slate-900">{p.namaProduk}</td>
                <td className="px-5 py-3 text-right text-slate-600 tabular-nums">
                  {p.hargaJual ? formatRupiah(p.hargaJual) : "-"}
                </td>
                <td className="px-5 py-3">
                  <button onClick={() => handleToggle(p)} className="cursor-pointer">
                    <Badge tone={p.isActive ? "green" : "slate"}>{p.isActive ? "Aktif" : "Nonaktif"}</Badge>
                  </button>
                </td>
                <td className="px-5 py-3 text-right">
                  <div className="flex items-center justify-end gap-1">
                    <button
                      onClick={() => openEdit(p)}
                      className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                      aria-label="Edit produk"
                    >
                      <Pencil className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(p)}
                      className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                      aria-label="Hapus produk"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {produkList.length === 0 && <EmptyRow colSpan={4} icon={Package} message="Belum ada produk." />}
          </tbody>
        </table>
      </div>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? "Edit Produk" : "Tambah Produk"}>
        <form key={editing?.id ?? "new"} action={formAction} className="space-y-4">
          {editing && <input type="hidden" name="id" value={editing.id} />}
          <div>
            <Label htmlFor="namaProduk">Nama Produk</Label>
            <Input id="namaProduk" name="namaProduk" required defaultValue={editing?.namaProduk} />
          </div>
          <div>
            <Label htmlFor="hargaJual">Harga Jual (Rp, opsional)</Label>
            <Input
              id="hargaJual"
              name="hargaJual"
              type="number"
              min={0}
              step={1}
              defaultValue={editing?.hargaJual ? editing.hargaJual.toString() : ""}
            />
          </div>
          {state.error && <ErrorMessage>{state.error}</ErrorMessage>}
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={() => setOpen(false)}>
              Batal
            </Button>
            <Button type="submit" disabled={isPending}>
              {isPending ? "Menyimpan..." : "Simpan"}
            </Button>
          </div>
        </form>
      </Modal>
    </Card>
  );
}
