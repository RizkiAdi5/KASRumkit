"use client";

import { useActionState, useEffect, useState } from "react";
import { toast } from "sonner";
import type { Kategori, Pegawai } from "@prisma/client";
import { Plus, ArrowUpRight, ArrowDownRight } from "lucide-react";
import { Modal } from "@/components/ui/modal";
import { Button } from "@/components/ui/button";
import { Input, Select, Textarea, Label } from "@/components/ui/input";
import { ErrorMessage } from "@/components/ui/error-message";
import { todayLocalISODate } from "@/lib/format";
import { createTransaksiAction, type ActionResult } from "./actions";

const initialState: ActionResult = { ok: false };

export function TransaksiModal({
  kategoriList,
  pegawaiList,
}: {
  kategoriList: Kategori[];
  pegawaiList: Pegawai[];
}) {
  const [open, setOpen] = useState(false);
  const [tipe, setTipe] = useState<"PEMASUKAN" | "PENGELUARAN">("PEMASUKAN");
  const [kategoriId, setKategoriId] = useState("");
  const [state, formAction, isPending] = useActionState(createTransaksiAction, initialState);

  const selectedKategori = kategoriList.find((k) => String(k.id) === kategoriId);
  const needsPegawai = selectedKategori?.isPegawaiBulanan ?? false;
  const filteredKategori = kategoriList.filter((k) => k.tipe === tipe);

  useEffect(() => {
    if (state.ok) {
      toast.success("Transaksi berhasil disimpan.");
      setOpen(false);
      setKategoriId("");
    }
  }, [state]);

  return (
    <>
      <Button onClick={() => setOpen(true)}>
        <Plus className="h-4 w-4" />
        Tambah Transaksi
      </Button>
      <Modal open={open} onClose={() => setOpen(false)} title="Tambah Transaksi">
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
                    onChange={() => {
                      setTipe(t);
                      setKategoriId("");
                    }}
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
          <div>
            <Label htmlFor="kategoriId">Kategori</Label>
            <Select
              id="kategoriId"
              name="kategoriId"
              required
              value={kategoriId}
              onChange={(e) => setKategoriId(e.target.value)}
            >
              <option value="">Pilih kategori</option>
              {filteredKategori.map((k) => (
                <option key={k.id} value={k.id}>
                  {k.namaKategori}
                </option>
              ))}
            </Select>
          </div>
          {needsPegawai && (
            <div>
              <Label htmlFor="pegawaiId">Pegawai</Label>
              <Select id="pegawaiId" name="pegawaiId" required>
                <option value="">Pilih pegawai</option>
                {pegawaiList.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.namaLengkap}
                    {p.departemen ? ` - ${p.departemen}` : ""}
                  </option>
                ))}
              </Select>
            </div>
          )}
          <div>
            <Label htmlFor="jumlah">Jumlah (Rp)</Label>
            <Input id="jumlah" name="jumlah" type="number" min={1} step={1} required />
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
