import { Download, ArrowUpRight, ArrowDownRight, Wallet, Landmark } from "lucide-react";
import { prisma } from "@/lib/prisma";
import { requireRole } from "@/lib/rbac";
import { formatRupiah, formatTanggal, BULAN_INDONESIA } from "@/lib/format";
import { resolveLaporanFilters, type LaporanSearchParams } from "@/lib/laporan-filters";
import { StatCard } from "@/components/ui/stat-card";
import { Badge } from "@/components/ui/badge";
import { Select, Input, Label } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Pagination } from "@/components/ui/pagination";
import { Card, CardHeader } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { EmptyRow } from "@/components/ui/empty-state";
import { MonthlyChart } from "./monthly-chart";

const PAGE_SIZE = 10;

function toNumber(value: { toNumber: () => number } | null | undefined) {
  return value ? value.toNumber() : 0;
}

export default async function LaporanPage({
  searchParams,
}: {
  searchParams: Promise<LaporanSearchParams & { page?: string }>;
}) {
  await requireRole(["ADMIN", "SUPERVISOR"]);
  const sp = await searchParams;
  const page = Math.max(1, Number(sp.page) || 1);
  const { fromDate, toDateExclusive, kategoriId, tipe, filterWhere, monthlyWhere } = resolveLaporanFilters(sp);

  const [total, transaksiList, kategoriList, sumIn, sumOut, allIn, allOut, categorySummaryRaw, monthlyRaw] =
    await Promise.all([
      prisma.transaksi.count({ where: filterWhere }),
      prisma.transaksi.findMany({
        where: filterWhere,
        orderBy: { tanggalTransaksi: "desc" },
        skip: (page - 1) * PAGE_SIZE,
        take: PAGE_SIZE,
        include: { kategori: true, pegawai: true },
      }),
      prisma.kategori.findMany({ orderBy: { namaKategori: "asc" } }),
      prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { ...filterWhere, tipeTransaksi: "PEMASUKAN" } }),
      prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { ...filterWhere, tipeTransaksi: "PENGELUARAN" } }),
      prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PEMASUKAN" } }),
      prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PENGELUARAN" } }),
      prisma.transaksi.groupBy({ by: ["kategoriId"], where: filterWhere, _sum: { jumlah: true } }),
      prisma.transaksi.findMany({
        where: monthlyWhere,
        select: { tanggalTransaksi: true, tipeTransaksi: true, jumlah: true },
      }),
    ]);

  const totalPemasukan = toNumber(sumIn._sum.jumlah);
  const totalPengeluaran = toNumber(sumOut._sum.jumlah);
  const saldoFiltered = totalPemasukan - totalPengeluaran;
  const saldoAkhir = toNumber(allIn._sum.jumlah) - toNumber(allOut._sum.jumlah);

  const kategoriMap = new Map(kategoriList.map((k) => [k.id, k]));
  const categorySummary = categorySummaryRaw
    .map((c) => ({ kategori: kategoriMap.get(c.kategoriId), total: toNumber(c._sum.jumlah) }))
    .filter((c): c is { kategori: NonNullable<typeof c.kategori>; total: number } => !!c.kategori)
    .sort((a, b) => b.total - a.total);
  const categoryMax = Math.max(1, ...categorySummary.map((c) => c.total));

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
  const monthlyData = Array.from(monthlyMap.values())
    .sort((a, b) => a.tahun - b.tahun || a.bulan - b.bulan)
    .map((m) => ({
      label: `${BULAN_INDONESIA[m.bulan].slice(0, 3)} ${m.tahun}`,
      Pemasukan: m.pemasukan,
      Pengeluaran: m.pengeluaran,
    }));

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  const fromDateStr = fromDate.toISOString().slice(0, 10);
  const toDateStr = new Date(toDateExclusive.getTime() - 86400000).toISOString().slice(0, 10);

  const exportParams = new URLSearchParams();
  exportParams.set("from_date", fromDateStr);
  exportParams.set("to_date", toDateStr);
  if (kategoriId) exportParams.set("kategori_id", String(kategoriId));
  if (tipe) exportParams.set("tipe", tipe);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Laporan"
        description="Laporan keuangan kas rumah sakit."
        action={
          <a href={`/api/export?${exportParams.toString()}`}>
            <Button variant="outline">
              <Download className="h-4 w-4" />
              Export Excel
            </Button>
          </a>
        }
      />

      <Card className="p-5">
        <form method="get" className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
          <div>
            <Label htmlFor="from_date">Dari Tanggal</Label>
            <Input id="from_date" name="from_date" type="date" defaultValue={fromDateStr} />
          </div>
          <div>
            <Label htmlFor="to_date">Sampai Tanggal</Label>
            <Input id="to_date" name="to_date" type="date" defaultValue={toDateStr} />
          </div>
          <div>
            <Label htmlFor="kategori_id">Kategori</Label>
            <Select id="kategori_id" name="kategori_id" defaultValue={kategoriId ? String(kategoriId) : "0"}>
              <option value="0">Semua Kategori</option>
              {kategoriList.map((k) => (
                <option key={k.id} value={k.id}>
                  {k.namaKategori}
                </option>
              ))}
            </Select>
          </div>
          <div>
            <Label htmlFor="tipe">Tipe</Label>
            <Select id="tipe" name="tipe" defaultValue={tipe ?? ""}>
              <option value="">Semua Tipe</option>
              <option value="PEMASUKAN">Pemasukan</option>
              <option value="PENGELUARAN">Pengeluaran</option>
            </Select>
          </div>
          <div className="flex items-end">
            <Button type="submit" className="w-full">
              Terapkan Filter
            </Button>
          </div>
        </form>
      </Card>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard label="Total Pemasukan" value={formatRupiah(totalPemasukan)} tone="positive" icon={ArrowUpRight} />
        <StatCard label="Total Pengeluaran" value={formatRupiah(totalPengeluaran)} tone="negative" icon={ArrowDownRight} />
        <StatCard
          label="Saldo Periode Ini"
          value={`${saldoFiltered < 0 ? "-" : ""}${formatRupiah(Math.abs(saldoFiltered))}`}
          tone={saldoFiltered < 0 ? "negative" : "default"}
          icon={Wallet}
        />
        <StatCard
          label="Saldo Akhir (Keseluruhan)"
          value={`${saldoAkhir < 0 ? "-" : ""}${formatRupiah(Math.abs(saldoAkhir))}`}
          tone={saldoAkhir < 0 ? "negative" : "default"}
          icon={Landmark}
        />
      </div>

      <Card className="p-5">
        <h2 className="mb-4 font-semibold text-slate-900">Grafik Bulanan</h2>
        <MonthlyChart data={monthlyData} />
      </Card>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-1">
          <CardHeader>
            <h2 className="font-semibold text-slate-900">Ringkasan Kategori</h2>
          </CardHeader>
          <ul className="divide-y divide-slate-50">
            {categorySummary.map(({ kategori, total: catTotal }) => (
              <li key={kategori.id} className="px-5 py-3 text-sm">
                <div className="mb-1.5 flex items-center justify-between">
                  <span className="flex items-center gap-2 text-slate-700">
                    <span
                      className={`h-1.5 w-1.5 rounded-full ${kategori.tipe === "PEMASUKAN" ? "bg-emerald-600" : "bg-red-600"}`}
                    />
                    {kategori.namaKategori}
                  </span>
                  <span className="font-medium text-slate-900 tabular-nums">{formatRupiah(catTotal)}</span>
                </div>
                <div className="h-1 overflow-hidden rounded-full bg-slate-100">
                  <div
                    className={`h-full rounded-full ${kategori.tipe === "PEMASUKAN" ? "bg-emerald-600" : "bg-red-600"}`}
                    style={{ width: `${Math.max(4, (catTotal / categoryMax) * 100)}%` }}
                  />
                </div>
              </li>
            ))}
            {categorySummary.length === 0 && <li className="px-5 py-10 text-center text-sm text-slate-400">Tidak ada data.</li>}
          </ul>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <h2 className="font-semibold text-slate-900">Detail Transaksi</h2>
          </CardHeader>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                  <th className="px-5 py-3">Tanggal</th>
                  <th className="px-5 py-3">Kategori</th>
                  <th className="px-5 py-3">Tipe</th>
                  <th className="px-5 py-3 text-right">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                {transaksiList.map((t) => (
                  <tr key={t.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                    <td className="px-5 py-3 whitespace-nowrap text-slate-600">{formatTanggal(t.tanggalTransaksi)}</td>
                    <td className="px-5 py-3 text-slate-800">{t.kategori.namaKategori}</td>
                    <td className="px-5 py-3">
                      <Badge tone={t.tipeTransaksi === "PEMASUKAN" ? "green" : "red"}>
                        {t.tipeTransaksi === "PEMASUKAN" ? "Pemasukan" : "Pengeluaran"}
                      </Badge>
                    </td>
                    <td className="px-5 py-3 text-right font-medium text-slate-900 tabular-nums">
                      {formatRupiah(t.jumlah.toNumber())}
                    </td>
                  </tr>
                ))}
                {transaksiList.length === 0 && <EmptyRow colSpan={4} message="Tidak ada transaksi pada periode ini." />}
              </tbody>
            </table>
          </div>
          <div className="border-t border-slate-100 px-5 py-3">
            <Pagination
              page={page}
              totalPages={totalPages}
              basePath="/laporan"
              searchParams={{
                from_date: fromDateStr,
                to_date: toDateStr,
                kategori_id: kategoriId ? String(kategoriId) : undefined,
                tipe,
              }}
            />
          </div>
        </Card>
      </div>
    </div>
  );
}
