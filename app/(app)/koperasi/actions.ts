"use server";

import { z } from "zod";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireUser, requireRoleAction } from "@/lib/rbac";
import { generateNomorTransaksi } from "@/lib/format";

export type ActionResult = { ok: boolean; error?: string };

const produkSchema = z.object({
  namaProduk: z.string().min(1, "Nama produk wajib diisi"),
  hargaJual: z.coerce.number().nonnegative().optional().or(z.literal("")),
});

export async function saveProdukAction(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);

  const hargaRaw = formData.get("hargaJual");
  const parsed = produkSchema.safeParse({
    namaProduk: formData.get("namaProduk"),
    hargaJual: hargaRaw ? hargaRaw : "",
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const id = formData.get("id");
  const data = {
    namaProduk: parsed.data.namaProduk,
    hargaJual: parsed.data.hargaJual === "" || parsed.data.hargaJual === undefined ? null : parsed.data.hargaJual,
  };

  if (id) {
    await prisma.koperasiProduk.update({ where: { id: Number(id) }, data });
  } else {
    await prisma.koperasiProduk.create({ data });
  }

  revalidatePath("/koperasi");
  return { ok: true };
}

export async function toggleProdukActiveAction(id: number, isActive: boolean) {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);
  await prisma.koperasiProduk.update({ where: { id }, data: { isActive } });
  revalidatePath("/koperasi");
}

export async function deleteProdukAction(id: number) {
  await requireRoleAction(["ADMIN", "SUPERVISOR"]);
  const count = await prisma.koperasiTransaksi.count({ where: { produkId: id } });
  if (count > 0) {
    throw new Error("Produk sudah dipakai pada transaksi dan tidak dapat dihapus.");
  }
  await prisma.koperasiProduk.delete({ where: { id } });
  revalidatePath("/koperasi");
}

const transaksiSchema = z.object({
  tanggalTransaksi: z.string().min(1, "Tanggal wajib diisi"),
  tipeTransaksi: z.enum(["PEMASUKAN", "PENGELUARAN"]),
  jumlah: z.coerce.number().positive("Jumlah harus lebih dari 0"),
  keterangan: z.string().optional(),
  produkId: z.string().optional(),
});

export async function createKoperasiTransaksiAction(
  _prev: ActionResult,
  formData: FormData
): Promise<ActionResult> {
  const user = await requireUser();

  const parsed = transaksiSchema.safeParse({
    tanggalTransaksi: formData.get("tanggalTransaksi"),
    tipeTransaksi: formData.get("tipeTransaksi"),
    jumlah: formData.get("jumlah"),
    keterangan: formData.get("keterangan") || undefined,
    produkId: formData.get("produkId") || undefined,
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const data = parsed.data;

  await prisma.koperasiTransaksi.create({
    data: {
      nomorTransaksi: generateNomorTransaksi(data.tipeTransaksi, "KOP"),
      tanggalTransaksi: new Date(data.tanggalTransaksi),
      tipeTransaksi: data.tipeTransaksi,
      jumlah: data.jumlah,
      keterangan: data.keterangan || null,
      produkId: data.produkId ? Number(data.produkId) : null,
      userId: Number(user.id),
    },
  });

  revalidatePath("/koperasi");
  return { ok: true };
}

export async function deleteKoperasiTransaksiAction(id: number) {
  const user = await requireUser();
  const transaksi = await prisma.koperasiTransaksi.findUnique({ where: { id } });
  if (!transaksi) return;
  if (user.role === "KASIR" && transaksi.userId !== Number(user.id)) {
    throw new Error("Forbidden");
  }
  await prisma.koperasiTransaksi.delete({ where: { id } });
  revalidatePath("/koperasi");
}
