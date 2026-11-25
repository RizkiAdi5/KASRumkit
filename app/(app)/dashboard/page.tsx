import { ArrowDownRight, ArrowUpRight, CalendarRange, Landmark, Wallet } from "lucide-react";
import { prisma } from "@/lib/prisma";
import { requireUser } from "@/lib/rbac";
import { formatRupiah, formatTanggal } from "@/lib/format";
import { StatCard } from "@/components/ui/stat-card";
import { Badge } from "@/components/ui/badge";
import { Card, CardHeader } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { EmptyRow } from "@/components/ui/empty-state";
import { DashboardRefresh } from "./dashboard-refresh";

function toNumber(value: { toNumber: () => number } | null | undefined) {
  return value ? value.toNumber() : 0;
}

export default async function DashboardPage() {
  const user = await requireUser();
  const scopeWhere = user.role === "KASIR" ? { userId: Number(user.id) } : {};

  const now = new Date();
  const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const todayEnd = new Date(todayStart.getTime() + 24 * 60 * 60 * 1000);
  const monthStart = new Date(now.getFullYear(), now.getMonth(), 1);
  const monthEnd = new Date(now.getFullYear(), now.getMonth() + 1, 1);

  const [todayIn, todayOut, monthIn, monthOut, allIn, allOut, recent] = await Promise.all([
    prisma.transaksi.aggregate({
      _sum: { jumlah: true },
      where: { ...scopeWhere, tipeTransaksi: "PEMASUKAN", tanggalTransaksi: { gte: todayStart, lt: todayEnd } },
    }),
    prisma.transaksi.aggregate({
      _sum: { jumlah: true },
      where: { ...scopeWhere, tipeTransaksi: "PENGELUARAN", tanggalTransaksi: { gte: todayStart, lt: todayEnd } },
    }),
    prisma.transaksi.aggregate({
      _sum: { jumlah: true },
      where: { ...scopeWhere, tipeTransaksi: "PEMASUKAN", tanggalTransaksi: { gte: monthStart, lt: monthEnd } },
    }),
    prisma.transaksi.aggregate({
      _sum: { jumlah: true },
      where: { ...scopeWhere, tipeTransaksi: "PENGELUARAN", tanggalTransaksi: { gte: monthStart, lt: monthEnd } },
    }),
    prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PEMASUKAN" } }),
    prisma.transaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PENGELUARAN" } }),
    prisma.transaksi.findMany({
      where: scopeWhere,
      orderBy: { createdAt: "desc" },
      take: 10,
      include: { kategori: true, pegawai: true, user: true },
    }),
  ]);

  const saldoHariIni = toNumber(todayIn._sum.jumlah) - toNumber(todayOut._sum.jumlah);
  const saldoBulanIni = toNumber(monthIn._sum.jumlah) - toNumber(monthOut._sum.jumlah);
  const saldoAkhir = toNumber(allIn._sum.jumlah) - toNumber(allOut._sum.jumlah);

  return (
    <div className="space-y-6">
      <DashboardRefresh />
      <PageHeader title="Dashboard" description="Ringkasan keuangan kas rumah sakit." />

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <StatCard label="Pemasukan Hari Ini" value={formatRupiah(toNumber(todayIn._sum.jumlah))} tone="positive" icon={ArrowUpRight} />
        <StatCard label="Pengeluaran Hari Ini" value={formatRupiah(toNumber(todayOut._sum.jumlah))} tone="negative" icon={ArrowDownRight} />
        <StatCard
          label="Saldo Hari Ini"
          value={`${saldoHariIni < 0 ? "-" : ""}${formatRupiah(Math.abs(saldoHariIni))}`}
          tone={saldoHariIni < 0 ? "negative" : "default"}
          icon={Wallet}
        />
        <StatCard
          label="Saldo Bulan Ini"
          value={`${saldoBulanIni < 0 ? "-" : ""}${formatRupiah(Math.abs(saldoBulanIni))}`}
          tone={saldoBulanIni < 0 ? "negative" : "default"}
          icon={CalendarRange}
        />
        <StatCard
          label="Saldo Akhir"
          value={`${saldoAkhir < 0 ? "-" : ""}${formatRupiah(Math.abs(saldoAkhir))}`}
          tone={saldoAkhir < 0 ? "negative" : "default"}
          icon={Landmark}
        />
      </div>

      <Card>
        <CardHeader>
          <h2 className="font-semibold text-slate-900">Transaksi Terbaru</h2>
        </CardHeader>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <th className="px-5 py-3">No. Transaksi</th>
                <th className="px-5 py-3">Tanggal</th>
                <th className="px-5 py-3">Kategori</th>
                <th className="px-5 py-3">Tipe</th>
                <th className="px-5 py-3 text-right">Jumlah</th>
                <th className="px-5 py-3">Oleh</th>
              </tr>
            </thead>
            <tbody>
              {recent.map((t) => (
                <tr key={t.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                  <td className="px-5 py-3 font-mono text-xs text-slate-500">{t.nomorTransaksi}</td>
                  <td className="px-5 py-3 whitespace-nowrap text-slate-600">{formatTanggal(t.tanggalTransaksi)}</td>
                  <td className="px-5 py-3 text-slate-800">{t.kategori.namaKategori}</td>
                  <td className="px-5 py-3">
                    <Badge tone={t.tipeTransaksi === "PEMASUKAN" ? "green" : "red"}>
                      {t.tipeTransaksi === "PEMASUKAN" ? "Pemasukan" : "Pengeluaran"}
                    </Badge>
                  </td>
                  <td className="px-5 py-3 text-right font-medium text-slate-900 tabular-nums">{formatRupiah(t.jumlah.toNumber())}</td>
                  <td className="px-5 py-3 text-slate-500">{t.user.namaLengkap}</td>
                </tr>
              ))}
              {recent.length === 0 && <EmptyRow colSpan={6} message="Belum ada transaksi." />}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
