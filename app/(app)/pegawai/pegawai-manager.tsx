"use client";

import { useActionState, useEffect, useState } from "react";
import { toast } from "sonner";
import type { Pegawai } from "@prisma/client";
import { Plus, Pencil, Trash2, History, Briefcase } from "lucide-react";
import { Modal } from "@/components/ui/modal";
import { Button } from "@/components/ui/button";
import { Input, Label } from "@/components/ui/input";
import { Pagination } from "@/components/ui/pagination";
import { Card } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { EmptyRow } from "@/components/ui/empty-state";
import { ErrorMessage } from "@/components/ui/error-message";
import { formatRupiah } from "@/lib/format";
import {
  savePegawaiAction,
  deletePegawaiAction,
  getRiwayatPegawaiAction,
  type ActionResult,
  type RiwayatResult,
} from "./actions";

type PegawaiRow = Pegawai & { totalAmount: number; transactionCount: number };

const initialState: ActionResult = { ok: false };

export function PegawaiManager({
  pegawaiList,
  page,
  totalPages,
}: {
  pegawaiList: PegawaiRow[];
  page: number;
  totalPages: number;
}) {
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Pegawai | null>(null);
  const [state, formAction, isPending] = useActionState(savePegawaiAction, initialState);

  const [riwayat, setRiwayat] = useState<RiwayatResult | null>(null);
  const [riwayatOpen, setRiwayatOpen] = useState(false);
  const [loadingRiwayat, setLoadingRiwayat] = useState(false);

  useEffect(() => {
    if (state.ok) {
      toast.success("Data pegawai berhasil disimpan.");
      setOpen(false);
      setEditing(null);
    }
  }, [state]);

  function openCreate() {
    setEditing(null);
    setOpen(true);
  }

  function openEdit(p: Pegawai) {
    setEditing(p);
    setOpen(true);
  }

  async function handleDelete(p: Pegawai) {
    if (!confirm(`Hapus pegawai "${p.namaLengkap}"?`)) return;
    try {
      await deletePegawaiAction(p.id);
      toast.success("Pegawai dihapus.");
    } catch {
      toast.error("Gagal menghapus pegawai.");
    }
  }

  async function openRiwayat(p: Pegawai) {
    setRiwayatOpen(true);
    setLoadingRiwayat(true);
    const result = await getRiwayatPegawaiAction(p.id);
    setRiwayat(result);
    setLoadingRiwayat(false);
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Pegawai"
        description="Data pegawai & riwayat kas bulanan."
        action={
          <Button onClick={openCreate}>
            <Plus className="h-4 w-4" />
            Tambah Pegawai
          </Button>
        }
      />

      <Card>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <th className="px-5 py-3">Nama</th>
                <th className="px-5 py-3">Departemen</th>
                <th className="px-5 py-3 text-right">Jumlah Setoran</th>
                <th className="px-5 py-3 text-right">Total Kas Bulanan</th>
                <th className="px-5 py-3" />
              </tr>
            </thead>
            <tbody>
              {pegawaiList.map((p) => (
                <tr key={p.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                  <td className="px-5 py-3 font-medium text-slate-900">{p.namaLengkap}</td>
                  <td className="px-5 py-3 text-slate-500">{p.departemen || "-"}</td>
                  <td className="px-5 py-3 text-right text-slate-600 tabular-nums">{p.transactionCount}x</td>
                  <td className="px-5 py-3 text-right font-medium text-slate-900 tabular-nums">
                    {formatRupiah(p.totalAmount)}
                  </td>
                  <td className="px-5 py-3 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => openRiwayat(p)}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Riwayat"
                      >
                        <History className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => openEdit(p)}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                        aria-label="Edit pegawai"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => handleDelete(p)}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                        aria-label="Hapus pegawai"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {pegawaiList.length === 0 && <EmptyRow colSpan={5} icon={Briefcase} message="Belum ada data pegawai." />}
            </tbody>
          </table>
        </div>
        <div className="border-t border-slate-100 px-5 py-3">
          <Pagination page={page} totalPages={totalPages} basePath="/pegawai" searchParams={{}} />
        </div>
      </Card>

      <Modal open={open} onClose={() => setOpen(false)} title={editing ? "Edit Pegawai" : "Tambah Pegawai"}>
        <form key={editing?.id ?? "new"} action={formAction} className="space-y-4">
          {editing && <input type="hidden" name="id" value={editing.id} />}
          <div>
            <Label htmlFor="namaLengkap">Nama Lengkap</Label>
            <Input id="namaLengkap" name="namaLengkap" required defaultValue={editing?.namaLengkap} />
          </div>
          <div>
            <Label htmlFor="departemen">Departemen</Label>
            <Input id="departemen" name="departemen" defaultValue={editing?.departemen ?? ""} />
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

      <Modal
        open={riwayatOpen}
        onClose={() => setRiwayatOpen(false)}
        title={riwayat ? `Riwayat - ${riwayat.namaLengkap}` : "Riwayat"}
      >
        {loadingRiwayat && <p className="text-sm text-slate-500">Memuat...</p>}
        {!loadingRiwayat && riwayat && (
          <div className="space-y-4">
            <div className="grid grid-cols-3 gap-3 text-center">
              <div className="rounded-lg bg-slate-50 p-3">
                <p className="text-xs text-slate-400">Total Setoran</p>
                <p className="font-semibold text-slate-900 tabular-nums">{formatRupiah(riwayat.totalPayments)}</p>
              </div>
              <div className="rounded-lg bg-slate-50 p-3">
                <p className="text-xs text-slate-400">Jumlah Transaksi</p>
                <p className="font-semibold text-slate-900 tabular-nums">{riwayat.totalCount}x</p>
              </div>
              <div className="rounded-lg bg-slate-50 p-3">
                <p className="text-xs text-slate-400">Rata-rata</p>
                <p className="font-semibold text-slate-900 tabular-nums">{formatRupiah(riwayat.average)}</p>
              </div>
            </div>
            <div>
              <h3 className="mb-2 text-sm font-semibold text-slate-700">Ringkasan Bulanan</h3>
              <div className="max-h-48 overflow-y-auto rounded-lg border border-slate-100">
                <table className="w-full text-sm">
                  <tbody>
                    {riwayat.monthly.map((m) => (
                      <tr key={`${m.bulan}-${m.tahun}`} className="border-b border-slate-50 last:border-0">
                        <td className="px-3 py-2 text-slate-600">
                          {m.bulan} {m.tahun}
                        </td>
                        <td className="px-3 py-2 text-right text-slate-500 tabular-nums">{m.count}x</td>
                        <td className="px-3 py-2 text-right font-medium text-slate-900 tabular-nums">
                          {formatRupiah(m.totalAmount)}
                        </td>
                      </tr>
                    ))}
                    {riwayat.monthly.length === 0 && (
                      <tr>
                        <td className="px-3 py-4 text-center text-slate-400">Belum ada riwayat.</td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
        {!loadingRiwayat && !riwayat && <p className="text-sm text-slate-500">Data tidak ditemukan.</p>}
      </Modal>
    </div>
  );
}
