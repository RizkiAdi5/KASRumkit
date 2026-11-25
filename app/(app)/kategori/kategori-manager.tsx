"use client";

import { useActionState, useEffect, useState } from "react";
import { toast } from "sonner";
import type { Kategori } from "@prisma/client";
import { Plus, Pencil, Trash2, Tag } from "lucide-react";
import { Modal } from "@/components/ui/modal";
import { Button } from "@/components/ui/button";
import { Input, Select, Textarea, Label } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { EmptyRow } from "@/components/ui/empty-state";
import { ErrorMessage } from "@/components/ui/error-message";
import {
  saveKategoriAction,
  toggleKategoriActiveAction,
  deleteKategoriAction,
  type ActionResult,
} from "./actions";

const initialState: ActionResult = { ok: false };

export function KategoriManager({ kategoriList }: { kategoriList: Kategori[] }) {
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Kategori | null>(null);
  const [state, formAction, isPending] = useActionState(saveKategoriAction, initialState);

  useEffect(() => {
    if (state.ok) {
      toast.success("Kategori berhasil disimpan.");
      setOpen(false);
      setEditing(null);
    }
  }, [state]);

  function openCreate() {
    setEditing(null);
    setOpen(true);
  }

  function openEdit(k: Kategori) {
    setEditing(k);
    setOpen(true);
  }

  async function handleToggle(k: Kategori) {
    try {
      await toggleKategoriActiveAction(k.id, !k.isActive);
      toast.success(k.isActive ? "Kategori dinonaktifkan." : "Kategori diaktifkan.");
    } catch {
      toast.error("Gagal mengubah status kategori.");
    }
  }

  async function handleDelete(k: Kategori) {
    if (!confirm(`Hapus kategori "${k.namaKategori}"?`)) return;
    try {
      await deleteKategoriAction(k.id);
      toast.success("Kategori dihapus.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Gagal menghapus kategori.");
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Kategori"
        description="Kelola kategori transaksi pemasukan & pengeluaran."
        action={
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Tambah Kategori
          </Button>
        }
      />

      <Card>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <th className="px-5 py-3">Nama Kategori</th>
                <th className="px-5 py-3">Tipe</th>
                <th className="px-5 py-3">Deskripsi</th>
                <th className="px-5 py-3">Status</th>
                <th className="px-5 py-3" />
              </tr>
            </thead>
            <tbody>
              {kategoriList.map((k) => (
                <tr key={k.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                  <td className="px-5 py-3 font-medium text-slate-900">
                    <div className="flex items-center gap-2">
                      {k.namaKategori}
                      {k.isPegawaiBulanan && <Badge tone="blue">Kas Bulanan Pegawai</Badge>}
                    </div>
                  </td>
                  <td className="px-5 py-3">
                    <Badge tone={k.tipe === "PEMASUKAN" ? "green" : "red"}>
                      {k.tipe === "PEMASUKAN" ? "Pemasukan" : "Pengeluaran"}
                    </Badge>
                  </td>
                  <td className="max-w-xs truncate px-5 py-3 text-slate-500">{k.deskripsi}</td>
                  <td className="px-5 py-3">
                    <button onClick={() => handleToggle(k)} className="cursor-pointer">
                      <Badge tone={k.isActive ? "green" : "slate"}>{k.isActive ? "Aktif" : "Nonaktif"}</Badge>
                    </button>
                  </td>
                  <td className="px-5 py-3 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => openEdit(k)}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                        aria-label="Edit kategori"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => handleDelete(k)}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                        aria-label="Hapus kategori"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {kategoriList.length === 0 && <EmptyRow colSpan={5} icon={Tag} message="Belum ada kategori." />}
            </tbody>
          </table>
        </div>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? "Edit Kategori" : "Tambah Kategori"}>
        <form key={editing?.id ?? "new"} action={formAction} className="space-y-4">
          {editing && <input type="hidden" name="id" value={editing.id} />}
          <div>
            <Label htmlFor="namaKategori">Nama Kategori</Label>
            <Input id="namaKategori" name="namaKategori" required defaultValue={editing?.namaKategori} />
          </div>
          <div>
            <Label htmlFor="tipe">Tipe</Label>
            <Select id="tipe" name="tipe" required defaultValue={editing?.tipe ?? "PEMASUKAN"}>
              <option value="PEMASUKAN">Pemasukan</option>
              <option value="PENGELUARAN">Pengeluaran</option>
            </Select>
          </div>
          <div>
            <Label htmlFor="deskripsi">Deskripsi</Label>
            <Textarea id="deskripsi" name="deskripsi" rows={2} defaultValue={editing?.deskripsi ?? ""} />
          </div>
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <input
              type="checkbox"
              name="isPegawaiBulanan"
              defaultChecked={editing?.isPegawaiBulanan}
              className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30"
            />
            Wajib pilih pegawai saat transaksi (kas bulanan pegawai)
          </label>
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
    </div>
  );
}
