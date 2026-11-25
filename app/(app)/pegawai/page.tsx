import { prisma } from "@/lib/prisma";
import { requireRole } from "@/lib/rbac";
import { PegawaiManager } from "./pegawai-manager";

const PAGE_SIZE = 20;

export default async function PegawaiPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  await requireRole(["ADMIN", "SUPERVISOR"]);
  const { page: pageParam } = await searchParams;
  const page = Math.max(1, Number(pageParam) || 1);

  const [total, pegawaiList] = await Promise.all([
    prisma.pegawai.count({ where: { isActive: true } }),
    prisma.pegawai.findMany({
      where: { isActive: true },
      orderBy: { namaLengkap: "asc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
    }),
  ]);

  const aggregates = await prisma.transaksi.groupBy({
    by: ["pegawaiId"],
    where: { pegawaiId: { in: pegawaiList.map((p) => p.id) }, kategori: { isPegawaiBulanan: true } },
    _sum: { jumlah: true },
    _count: { _all: true },
  });
  const aggMap = new Map(aggregates.map((a) => [a.pegawaiId, a]));

  const enriched = pegawaiList.map((p) => ({
    ...p,
    totalAmount: aggMap.get(p.id)?._sum.jumlah?.toNumber() ?? 0,
    transactionCount: aggMap.get(p.id)?._count._all ?? 0,
  }));

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  return <PegawaiManager pegawaiList={enriched} page={page} totalPages={totalPages} />;
}
