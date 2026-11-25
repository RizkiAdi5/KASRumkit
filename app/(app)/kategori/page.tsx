import { prisma } from "@/lib/prisma";
import { requireRole } from "@/lib/rbac";
import { KategoriManager } from "./kategori-manager";

export default async function KategoriPage() {
  await requireRole(["ADMIN", "SUPERVISOR"]);

  const kategoriList = await prisma.kategori.findMany({ orderBy: { namaKategori: "asc" } });

  return <KategoriManager kategoriList={kategoriList} />;
}
