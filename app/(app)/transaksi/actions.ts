"use server";

import { z } from "zod";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireUser } from "@/lib/rbac";
import { generateNomorTransaksi } from "@/lib/format";

const schema = z.object({
  tanggalTransaksi: z.string().min(1, "Tanggal wajib diisi"),
  kategoriId: z.coerce.number().int().positive("Kategori wajib dipilih"),
  tipeTransaksi: z.enum(["PEMASUKAN", "PENGELUARAN"]),
  jumlah: z.coerce.number().positive("Jumlah harus lebih dari 0"),
  keterangan: z.string().optional(),
  pegawaiId: z.string().optional(),
});

export type ActionResult = { ok: boolean; error?: string };

export async function createTransaksiAction(
  _prev: ActionResult,
  formData: FormData
): Promise<ActionResult> {
  const user = await requireUser();

  const parsed = schema.safeParse({
    tanggalTransaksi: formData.get("tanggalTransaksi"),
    kategoriId: formData.get("kategoriId"),
    tipeTransaksi: formData.get("tipeTransaksi"),
    jumlah: formData.get("jumlah"),
    keterangan: formData.get("keterangan") || undefined,
    pegawaiId: formData.get("pegawaiId") || undefined,
  });

  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const data = parsed.data;
  const kategori = await prisma.kategori.findUnique({ where: { id: data.kategoriId } });
  if (!kategori || !kategori.isActive) {
    return { ok: false, error: "Kategori tidak ditemukan." };
  }

  let pegawaiId: number | null = null;
  if (kategori.isPegawaiBulanan) {
    if (!data.pegawaiId) return { ok: false, error: "Pegawai wajib dipilih untuk kategori ini." };
    pegawaiId = Number(data.pegawaiId);
  }

  await prisma.transaksi.create({
    data: {
      nomorTransaksi: generateNomorTransaksi(data.tipeTransaksi),
      tanggalTransaksi: new Date(data.tanggalTransaksi),
      kategoriId: data.kategoriId,
      tipeTransaksi: data.tipeTransaksi,
      jumlah: data.jumlah,
      keterangan: data.keterangan || null,
      userId: Number(user.id),
      pegawaiId,
    },
  });

  revalidatePath("/transaksi");
  revalidatePath("/dashboard");
  return { ok: true };
}

export async function deleteTransaksiAction(id: number) {
  const user = await requireUser();
  const transaksi = await prisma.transaksi.findUnique({ where: { id } });
  if (!transaksi) return;
  if (user.role === "KASIR" && transaksi.userId !== Number(user.id)) {
    throw new Error("Forbidden");
  }
  await prisma.transaksi.delete({ where: { id } });
  revalidatePath("/transaksi");
  revalidatePath("/dashboard");
}
