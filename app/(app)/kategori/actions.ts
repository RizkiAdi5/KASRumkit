"use server";

import { z } from "zod";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireRoleAction } from "@/lib/rbac";

const schema = z.object({
  namaKategori: z.string().min(1, "Nama kategori wajib diisi"),
  tipe: z.enum(["PEMASUKAN", "PENGELUARAN"]),
  deskripsi: z.string().optional(),
  isPegawaiBulanan: z.boolean().optional(),
});

export type ActionResult = { ok: boolean; error?: string };

function parseForm(formData: FormData) {
  return schema.safeParse({
    namaKategori: formData.get("namaKategori"),
    tipe: formData.get("tipe"),
    deskripsi: formData.get("deskripsi") || undefined,
    isPegawaiBulanan: formData.get("isPegawaiBulanan") === "on",
  });
}

export async function saveKategoriAction(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);

  const parsed = parseForm(formData);
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const id = formData.get("id");
  const data = {
    namaKategori: parsed.data.namaKategori,
    tipe: parsed.data.tipe,
    deskripsi: parsed.data.deskripsi || null,
    isPegawaiBulanan: !!parsed.data.isPegawaiBulanan,
  };

  if (id) {
    await prisma.kategori.update({ where: { id: Number(id) }, data });
  } else {
    await prisma.kategori.create({ data });
  }

  revalidatePath("/kategori");
  revalidatePath("/transaksi");
  return { ok: true };
}

export async function toggleKategoriActiveAction(id: number, isActive: boolean) {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);
  await prisma.kategori.update({ where: { id }, data: { isActive } });
  revalidatePath("/kategori");
  revalidatePath("/transaksi");
}

export async function deleteKategoriAction(id: number) {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);
  const count = await prisma.transaksi.count({ where: { kategoriId: id } });
  if (count > 0) {
    throw new Error("Kategori sudah dipakai pada transaksi dan tidak dapat dihapus.");
  }
  await prisma.kategori.delete({ where: { id } });
  revalidatePath("/kategori");
}
