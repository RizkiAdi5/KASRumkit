"use client";

import { useActionState, useEffect, useState } from "react";
import { toast } from "sonner";
import type { KoperasiProduk } from "@prisma/client";
import { Plus, ArrowUpRight, ArrowDownRight } from "lucide-react";
import { Modal } from "@/components/ui/modal";
import { Button } from "@/components/ui/button";
import { Input, Select, Textarea, Label } from "@/components/ui/input";
import { ErrorMessage } from "@/components/ui/error-message";
import { todayLocalISODate } from "@/lib/format";
import { createKoperasiTransaksiAction, type ActionResult } from "./actions";

const initialState: ActionResult = { ok: false };

type ProdukRow = Omit<KoperasiProduk, "hargaJual"> & { hargaJual: number | null };

export function TransaksiKoperasiModal({ produkList }: { produkList: ProdukRow[] }) {
  const [open, setOpen] = useState(false);
  const [tipe, setTipe] = useState<"PEMASUKAN" | "PENGELUARAN">("PEMASUKAN");
  const [produkId, setProdukId] = useState("");
  const [state, formAction, isPending] = useActionState(createKoperasiTransaksiAction, initialState);

  useEffect(() => {
    if (state.ok) {
      toast.success("Transaksi koperasi berhasil disimpan.");
      setOpen(false);
      setProdukId("");
    }
  }, [state]);

  function applyHarga(id: string) {
    setProdukId(id);
    const produk = produkList.find((p) => String(p.id) === id);
    if (produk?.hargaJual) {
      const input = document.getElementById("koperasi-jumlah") as HTMLInputElement | null;
      if (input && !input.value) input.value = produk.hargaJual.toString();
    }
  }

  return (
    <>
      <Button onClick={() => setOpen(true)}>
        <Plus className="h-4 w-4" />
        Tambah Transaksi
      </Button>
      <Modal open={open} onClose={() => setOpen(false)} title="Tambah Transaksi Koperasi">
        <form action={formAction} className="space-y-4">
          <div>
            <Label>Tipe Transaksi</Label>
            <div className="flex gap-2">
              {(
                [
                  { value: "PEMASUKAN", label: "Pemasukan", Icon: ArrowUpRight },
                  { value: "PENGELUARAN", label: "Pengeluaran", Icon: ArrowDownRight },
                ] as const
              ).map(({ value: t, label, Icon }) => (
                <label
                  key={t}
                  className={`flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                    tipe === t
                      ? "border-indigo-500 bg-indigo-50 text-indigo-700"
                      : "border-slate-200 text-slate-500 hover:bg-slate-50"
                  }`}
                >
                  <input
                    type="radio"
                    name="tipeTransaksi"
                    value={t}
                    checked={tipe === t}
                    onChange={() => setTipe(t)}
                    className="sr-only"
                  />
                  <Icon className="h-4 w-4" />
                  {label}
                </label>
              ))}
            </div>
          </div>
          <div>
            <Label htmlFor="tanggalTransaksi">Tanggal</Label>
            <Input
              id="tanggalTransaksi"
              name="tanggalTransaksi"
              type="date"
              required
              defaultValue={todayLocalISODate()}
            />
          </div>
          {tipe === "PEMASUKAN" && (
            <div>
              <Label htmlFor="produkId">Produk (opsional)</Label>
              <Select id="produkId" name="produkId" value={produkId} onChange={(e) => applyHarga(e.target.value)}>
                <option value="">Tanpa produk spesifik</option>
                {produkList.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.namaProduk}
                  </option>
                ))}
              </Select>
            </div>
          )}
          <div>
            <Label htmlFor="koperasi-jumlah">Jumlah (Rp)</Label>
            <Input id="koperasi-jumlah" name="jumlah" type="number" min={1} step={1} required />
          </div>
          <div>
            <Label htmlFor="keterangan">Keterangan</Label>
            <Textarea id="keterangan" name="keterangan" rows={2} />
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
    </>
  );
}
