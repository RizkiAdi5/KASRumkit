import { prisma } from "@/lib/prisma";
import { requireUser } from "@/lib/rbac";
import { formatRupiah, formatTanggal } from "@/lib/format";
import { Pagination } from "@/components/ui/pagination";
import { Badge } from "@/components/ui/badge";
import { Card } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { EmptyRow } from "@/components/ui/empty-state";
import { TransaksiModal } from "./transaksi-modal";
import { DeleteButton } from "./delete-button";

const PAGE_SIZE = 10;

export default async function TransaksiPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const user = await requireUser();
  const { page: pageParam } = await searchParams;
  const page = Math.max(1, Number(pageParam) || 1);

  const scopeWhere = user.role === "KASIR" ? { userId: Number(user.id) } : {};

  const [total, transaksi, kategoriList, pegawaiList] = await Promise.all([
    prisma.transaksi.count({ where: scopeWhere }),
    prisma.transaksi.findMany({
      where: scopeWhere,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      include: { kategori: true, pegawai: true, user: true },
    }),
    prisma.kategori.findMany({ where: { isActive: true }, orderBy: { namaKategori: "asc" } }),
    prisma.pegawai.findMany({ where: { isActive: true }, orderBy: { namaLengkap: "asc" } }),
  ]);

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  return (
    <div className="space-y-6">
      <PageHeader
        title="Transaksi"
        description={user.role === "KASIR" ? "Transaksi yang Anda input." : "Semua transaksi kas."}
        action={<TransaksiModal kategoriList={kategoriList} pegawaiList={pegawaiList} />}
      />

      <Card>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <th className="px-5 py-3">No. Transaksi</th>
                <th className="px-5 py-3">Tanggal</th>
                <th className="px-5 py-3">Kategori</th>
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
                  <td className="px-5 py-3 text-slate-800">
                    {t.kategori.namaKategori}
                    {t.pegawai && <span className="block text-xs text-slate-400">{t.pegawai.namaLengkap}</span>}
                  </td>
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
              {transaksi.length === 0 && <EmptyRow colSpan={8} message="Belum ada transaksi." />}
            </tbody>
          </table>
        </div>
        <div className="border-t border-slate-100 px-5 py-3">
          <Pagination page={page} totalPages={totalPages} basePath="/transaksi" searchParams={{}} />
        </div>
      </Card>
    </div>
  );
}
