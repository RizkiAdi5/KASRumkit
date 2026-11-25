import type { Prisma, TipeTransaksi } from "@prisma/client";

export type LaporanSearchParams = {
  from_date?: string;
  to_date?: string;
  kategori_id?: string;
  tipe?: string;
};

export function resolveLaporanFilters(sp: LaporanSearchParams) {
  const now = new Date();
  const defaultFrom = new Date(now.getFullYear(), now.getMonth(), 1);
  const defaultTo = now;

  const fromDate = sp.from_date ? new Date(sp.from_date) : defaultFrom;
  const toDateRaw = sp.to_date ? new Date(sp.to_date) : defaultTo;
  const toDateExclusive = new Date(toDateRaw.getFullYear(), toDateRaw.getMonth(), toDateRaw.getDate() + 1);
  const kategoriId = sp.kategori_id ? Number(sp.kategori_id) : 0;
  const tipe: TipeTransaksi | undefined =
    sp.tipe === "PEMASUKAN" || sp.tipe === "PENGELUARAN" ? (sp.tipe as TipeTransaksi) : undefined;

  const filterWhere: Prisma.TransaksiWhereInput = {
    tanggalTransaksi: { gte: fromDate, lt: toDateExclusive },
    ...(kategoriId ? { kategoriId } : {}),
    ...(tipe ? { tipeTransaksi: tipe } : {}),
  };

  const monthlyWhere: Prisma.TransaksiWhereInput = {
    tanggalTransaksi: { gte: fromDate, lt: toDateExclusive },
  };

  return { fromDate, toDateExclusive, kategoriId, tipe, filterWhere, monthlyWhere };
}
