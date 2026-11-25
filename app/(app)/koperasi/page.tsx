import { ArrowUpRight, ArrowDownRight, Wallet } from "lucide-react";
import { prisma } from "@/lib/prisma";
import { requireUser } from "@/lib/rbac";
import { formatRupiah, formatTanggal } from "@/lib/format";
import { StatCard } from "@/components/ui/stat-card";
import { Badge } from "@/components/ui/badge";
import { Card, CardHeader } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { Pagination } from "@/components/ui/pagination";
import { EmptyRow } from "@/components/ui/empty-state";
import { ProdukManager } from "./produk-manager";
import { TransaksiKoperasiModal } from "./transaksi-koperasi-modal";
import { DeleteButton } from "./delete-button";

const PAGE_SIZE = 10;

function toNumber(value: { toNumber: () => number } | null | undefined) {
  return value ? value.toNumber() : 0;
}

export default async function KoperasiPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const user = await requireUser();
  const { page: pageParam } = await searchParams;
  const page = Math.max(1, Number(pageParam) || 1);

  const scopeWhere = user.role === "KASIR" ? { userId: Number(user.id) } : {};
  const canManageProduk = user.role === "ADMIN" || user.role === "SUPERVISOR";

  const [total, transaksi, produkList, allIn, allOut] = await Promise.all([
    prisma.koperasiTransaksi.count({ where: scopeWhere }),
    prisma.koperasiTransaksi.findMany({
      where: scopeWhere,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      include: { produk: true, user: true },
    }),
    prisma.koperasiProduk.findMany({ orderBy: { namaProduk: "asc" } }),
    prisma.koperasiTransaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PEMASUKAN" } }),
    prisma.koperasiTransaksi.aggregate({ _sum: { jumlah: true }, where: { tipeTransaksi: "PENGELUARAN" } }),
  ]);

  const saldo = toNumber(allIn._sum.jumlah) - toNumber(allOut._sum.jumlah);
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  // Decimal instances can't cross the server->client boundary as props — plain-number them first.
  const produkSerialized = produkList.map((p) => ({ ...p, hargaJual: p.hargaJual ? p.hargaJual.toNumber() : null }));
  const produkAktif = produkSerialized.filter((p) => p.isActive);

  return (
    <div className="space-y-6">
      <PageHeader
        title="Koperasi Ruang"
        description="Pembukuan toko mini / koperasi ruang — jajanan, minuman, dan kebutuhan lain."
        action={<TransaksiKoperasiModal produkList={produkAktif} />}
      />

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatCard label="Total Pemasukan" value={formatRupiah(toNumber(allIn._sum.jumlah))} tone="positive" icon={ArrowUpRight} />
        <StatCard label="Total Pengeluaran" value={formatRupiah(toNumber(allOut._sum.jumlah))} tone="negative" icon={ArrowDownRight} />
        <StatCard
          label="Saldo Koperasi"
          value={`${saldo < 0 ? "-" : ""}${formatRupiah(Math.abs(saldo))}`}
          tone={saldo < 0 ? "negative" : "default"}
          icon={Wallet}
        />
      </div>

      {canManageProduk && <ProdukManager produkList={produkSerialized} />}

      <Card>
        <CardHeader>
          <h2 className="font-semibold text-slate-900">Transaksi Koperasi</h2>
        </CardHeader>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <th className="px-5 py-3">No. Transaksi</th>
                <th className="px-5 py-3">Tanggal</th>
                <th className="px-5 py-3">Produk</th>
                <th className="px-5 py-3">Tipe</th>
                <th className="px-5 py-3 text-right">Jumlah</th>
                <th className="px-5 py-3">Keterangan</th>
                <th className="px-5 py-3">Oleh</th>
                <th className="px-5 py-3" />
              </tr>
            </thead>
            <tbody>
              {transaksi.map((t) => (
                <tr key={t.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                  <td className="px-5 py-3 font-mono text-xs text-slate-500">{t.nomorTransaksi}</td>
                  <td className="px-5 py-3 whitespace-nowrap text-slate-600">{formatTanggal(t.tanggalTransaksi)}</td>
                  <td className="px-5 py-3 text-slate-800">{t.produk?.namaProduk ?? "-"}</td>
                  <td className="px-5 py-3">
                    <Badge tone={t.tipeTransaksi === "PEMASUKAN" ? "green" : "red"}>
                      {t.tipeTransaksi === "PEMASUKAN" ? "Pemasukan" : "Pengeluaran"}
                    </Badge>
                  </td>
                  <td className="px-5 py-3 text-right font-medium text-slate-900 tabular-nums">
                    {formatRupiah(t.jumlah.toNumber())}
                  </td>
                  <td className="max-w-[200px] truncate px-5 py-3 text-slate-500">{t.keterangan}</td>
                  <td className="px-5 py-3 text-slate-500">{t.user.namaLengkap}</td>
                  <td className="px-5 py-3 text-right">
                    {(user.role !== "KASIR" || t.userId === Number(user.id)) && <DeleteButton id={t.id} />}
                  </td>
                </tr>
              ))}
              {transaksi.length === 0 && <EmptyRow colSpan={8} message="Belum ada transaksi koperasi." />}
            </tbody>
          </table>
        </div>
        <div className="border-t border-slate-100 px-5 py-3">
          <Pagination page={page} totalPages={totalPages} basePath="/koperasi" searchParams={{}} />
        </div>
      </Card>
    </div>
  );
}
