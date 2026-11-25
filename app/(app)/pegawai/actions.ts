"use server";

import { z } from "zod";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireRoleAction } from "@/lib/rbac";
import { BULAN_INDONESIA } from "@/lib/format";

const schema = z.object({
  namaLengkap: z.string().min(1, "Nama lengkap wajib diisi"),
  departemen: z.string().optional(),
});

export type ActionResult = { ok: boolean; error?: string };

export async function savePegawaiAction(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);

  const parsed = schema.safeParse({
    namaLengkap: formData.get("namaLengkap"),
    departemen: formData.get("departemen") || undefined,
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const id = formData.get("id");
  const data = { namaLengkap: parsed.data.namaLengkap, departemen: parsed.data.departemen || null };

  if (id) {
    await prisma.pegawai.update({ where: { id: Number(id) }, data });
  } else {
    await prisma.pegawai.create({ data });
  }

  revalidatePath("/pegawai");
  revalidatePath("/transaksi");
  return { ok: true };
}

export async function deletePegawaiAction(id: number) {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);
  await prisma.pegawai.delete({ where: { id } });
  revalidatePath("/pegawai");
}

export type RiwayatResult = {
  namaLengkap: string;
  totalPayments: number;
  totalCount: number;
  average: number;
  monthly: { bulan: string; tahun: number; totalAmount: number; count: number }[];
};

export async function getRiwayatPegawaiAction(employeeId: number): Promise<RiwayatResult | null> {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);

  const pegawai = await prisma.pegawai.findUnique({ where: { id: employeeId } });
  if (!pegawai || !pegawai.isActive) return null;

  const payments = await prisma.transaksi.findMany({
    where: { pegawaiId: employeeId, kategori: { isPegawaiBulanan: true } },
    orderBy: { tanggalTransaksi: "desc" },
  });

  const totalPayments = payments.reduce((sum, p) => sum + p.jumlah.toNumber(), 0);
  const totalCount = payments.length;
  const average = totalCount > 0 ? totalPayments / totalCount : 0;

  const monthlyMap = new Map<string, { bulan: number; tahun: number; total: number; count: number }>();
  for (const p of payments) {
    const d = p.tanggalTransaksi;
    const key = `${d.getFullYear()}-${d.getMonth()}`;
    const entry = monthlyMap.get(key) ?? { bulan: d.getMonth(), tahun: d.getFullYear(), total: 0, count: 0 };
    entry.total += p.jumlah.toNumber();
    entry.count += 1;
    monthlyMap.set(key, entry);
  }

  const monthly = Array.from(monthlyMap.values())
    .sort((a, b) => b.tahun - a.tahun || b.bulan - a.bulan)
    .map((m) => ({ bulan: BULAN_INDONESIA[m.bulan], tahun: m.tahun, totalAmount: m.total, count: m.count }));

  return {
    namaLengkap: pegawai.namaLengkap,
    totalPayments,
    totalCount,
    average,
    monthly,
  };
}
