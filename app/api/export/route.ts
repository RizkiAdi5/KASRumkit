import { NextRequest, NextResponse } from "next/server";
import ExcelJS from "exceljs";
import { prisma } from "@/lib/prisma";
import { requireRoleAction } from "@/lib/rbac";
import { resolveLaporanFilters } from "@/lib/laporan-filters";
import { formatRupiah, formatTanggal, BULAN_INDONESIA } from "@/lib/format";

function toNumber(value: { toNumber: () => number } | null | undefined) {
  return value ? value.toNumber() : 0;
}

export async function GET(request: NextRequest) {
  let user;
  try {
    user = await requireRoleAction(["ADMIN", "SUPERVISOR"]);
  } catch {
    return NextResponse.json({ error: "Forbidden" }, { status: 403 });
  }

  const sp = Object.fromEntries(request.nextUrl.searchParams);
  const { fromDate, toDateExclusive, filterWhere, monthlyWhere } = resolveLaporanFilters(sp);

  const [transaksiList, allIn, allOut, categorySummaryRaw, monthlyRaw, kategoriList] = await Promise.all([
    prisma.transaksi.findMany({
      where: filterWhere,
      orderBy: { tanggalTransaksi: "asc" },
      include: { kategori: true, pegawai: true, user: true },
    }),
    prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PEMASUKAN" } }),
    prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PENGELUARAN" } }),
    prisma.transaksi.groupBy({ by: ["kategoriId"], where: filterWhere, _sum: { jumlah: true } }),
    prisma.transaksi.findMany({
      where: monthlyWhere,
      select: { tanggalTransaksi: true, tipeTransaksi: true, jumlah: true },
    }),
    prisma.kategori.findMany(),
  ]);

  const totalPemasukan = transaksiList
    .filter((t) => t.tipeTransaksi === "PEMASUKAN")
    .reduce((sum, t) => sum + t.jumlah.toNumber(), 0);
  const totalPengeluaran = transaksiList
    .filter((t) => t.tipeTransaksi === "PENGELUARAN")
    .reduce((sum, t) => sum + t.jumlah.toNumber(), 0);
  const saldoAkhirGlobal = toNumber(allIn._sum.jumlah) - toNumber(allOut._sum.jumlah);

  const kategoriMap = new Map(kategoriList.map((k) => [k.id, k]));
  const categorySummary = categorySummaryRaw
    .map((c) => ({ kategori: kategoriMap.get(c.kategoriId), total: toNumber(c._sum.jumlah) }))
    .filter((c): c is { kategori: NonNullable<typeof c.kategori>; total: number } => !!c.kategori)
    .sort((a, b) => b.total - a.total);

  const monthlyMap = new Map<string, { bulan: number; tahun: number; pemasukan: number; pengeluaran: number }>();
  for (const t of monthlyRaw) {
    const d = t.tanggalTransaksi;
    const key = `${d.getFullYear()}-${d.getMonth()}`;
    const entry = monthlyMap.get(key) ?? { bulan: d.getMonth(), tahun: d.getFullYear(), pemasukan: 0, pengeluaran: 0 };
    const amt = t.jumlah.toNumber();
    if (t.tipeTransaksi === "PEMASUKAN") entry.pemasukan += amt;
    else entry.pengeluaran += amt;
    monthlyMap.set(key, entry);
  }
  const monthlyData = Array.from(monthlyMap.values()).sort((a, b) => a.tahun - b.tahun || a.bulan - b.bulan);

  const fromDateStr = fromDate.toISOString().slice(0, 10);
  const toDate = new Date(toDateExclusive.getTime() - 86400000);
  const toDateStr = toDate.toISOString().slice(0, 10);

  const workbook = new ExcelJS.Workbook();
  workbook.creator = user.name ?? user.username;
  workbook.created = new Date();

  const summarySheet = workbook.addWorksheet("Ringkasan");
  summarySheet.columns = [{ width: 30 }, { width: 24 }];
  summarySheet.addRow(["Laporan Kas - KAS Ruang Gelatik"]).font = { bold: true, size: 14 };
  summarySheet.addRow([`Periode: ${formatTanggal(fromDate)} s/d ${formatTanggal(toDate)}`]);
  summarySheet.addRow([]);
  summarySheet.addRow(["Total Pemasukan (periode)", totalPemasukan]);
  summarySheet.addRow(["Total Pengeluaran (periode)", totalPengeluaran]);
  summarySheet.addRow(["Saldo Periode Ini", totalPemasukan - totalPengeluaran]);
  summarySheet.addRow(["Saldo Akhir (keseluruhan)", saldoAkhirGlobal]);
  summarySheet.addRow([]);
  summarySheet.addRow(["Diekspor oleh", user.name ?? user.username]);
  summarySheet.addRow(["Tanggal ekspor", formatTanggal(new Date())]);
  for (const row of [4, 5, 6, 7]) summarySheet.getCell(`B${row}`).numFmt = '"Rp"#,##0';

  const categorySheet = workbook.addWorksheet("Ringkasan Kategori");
  categorySheet.columns = [{ width: 30 }, { width: 16 }, { width: 20 }];
  categorySheet.addRow(["Kategori", "Tipe", "Total"]).font = { bold: true };
  for (const c of categorySummary) {
    categorySheet.addRow([c.kategori.namaKategori, c.kategori.tipe === "PEMASUKAN" ? "Pemasukan" : "Pengeluaran", c.total]);
  }
  categorySheet.getColumn(3).numFmt = '"Rp"#,##0';

  const monthlySheet = workbook.addWorksheet("Ringkasan Bulanan");
  monthlySheet.columns = [{ width: 20 }, { width: 18 }, { width: 18 }, { width: 18 }];
  monthlySheet.addRow(["Bulan", "Pemasukan", "Pengeluaran", "Saldo"]).font = { bold: true };
  for (const m of monthlyData) {
    monthlySheet.addRow([`${BULAN_INDONESIA[m.bulan]} ${m.tahun}`, m.pemasukan, m.pengeluaran, m.pemasukan - m.pengeluaran]);
  }
  for (const col of [2, 3, 4]) monthlySheet.getColumn(col).numFmt = '"Rp"#,##0';

  const detailSheet = workbook.addWorksheet("Detail Transaksi");
  detailSheet.columns = [
    { width: 18 },
    { width: 14 },
    { width: 28 },
    { width: 14 },
    { width: 18 },
    { width: 30 },
    { width: 20 },
    { width: 20 },
  ];
  detailSheet.addRow(["No. Transaksi", "Tanggal", "Kategori", "Tipe", "Jumlah", "Keterangan", "Pegawai", "Oleh"]).font = {
    bold: true,
  };
  for (const t of transaksiList) {
    detailSheet.addRow([
      t.nomorTransaksi,
      formatTanggal(t.tanggalTransaksi),
      t.kategori.namaKategori,
      t.tipeTransaksi === "PEMASUKAN" ? "Pemasukan" : "Pengeluaran",
      t.jumlah.toNumber(),
      t.keterangan ?? "",
      t.pegawai?.namaLengkap ?? "",
      t.user.namaLengkap,
    ]);
  }
  detailSheet.getColumn(5).numFmt = '"Rp"#,##0';

  const buffer = await workbook.xlsx.writeBuffer();

  return new NextResponse(buffer, {
    headers: {
      "Content-Type": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
      "Content-Disposition": `attachment; filename="laporan-kas-${fromDateStr}_${toDateStr}.xlsx"`,
    },
  });
}
