import { PrismaClient } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

async function main() {
  const passwordHash = await bcrypt.hash("admin123", 10);

  await prisma.user.upsert({
    where: { username: "admin" },
    update: {},
    create: {
      username: "admin",
      passwordHash,
      namaLengkap: "Administrator",
      email: "admin@rumkit.com",
      role: "ADMIN",
    },
  });

  const kategoriSeed = [
    { namaKategori: "Pendapatan Rawat Jalan", tipe: "PEMASUKAN" as const, deskripsi: "Pendapatan dari layanan rawat jalan" },
    { namaKategori: "Pendapatan Rawat Inap", tipe: "PEMASUKAN" as const, deskripsi: "Pendapatan dari layanan rawat inap" },
    { namaKategori: "Pendapatan Laboratorium", tipe: "PEMASUKAN" as const, deskripsi: "Pendapatan dari layanan laboratorium" },
    { namaKategori: "Pendapatan Farmasi", tipe: "PEMASUKAN" as const, deskripsi: "Pendapatan dari layanan farmasi" },
    { namaKategori: "Kas Bulanan Pegawai", tipe: "PEMASUKAN" as const, deskripsi: "Setoran kas bulanan pegawai", isPegawaiBulanan: true },
    { namaKategori: "Gaji Karyawan", tipe: "PENGELUARAN" as const, deskripsi: "Pengeluaran untuk gaji karyawan" },
    { namaKategori: "Biaya Operasional", tipe: "PENGELUARAN" as const, deskripsi: "Biaya operasional rumah sakit" },
    { namaKategori: "Biaya Utilitas", tipe: "PENGELUARAN" as const, deskripsi: "Biaya listrik, air, dan utilitas lainnya" },
    { namaKategori: "Biaya Maintenance", tipe: "PENGELUARAN" as const, deskripsi: "Biaya pemeliharaan peralatan dan gedung" },
  ];

  for (const k of kategoriSeed) {
    const existing = await prisma.kategori.findFirst({ where: { namaKategori: k.namaKategori } });
    if (!existing) {
      await prisma.kategori.create({ data: k });
    }
  }

  console.log("Seed complete. Login: admin / admin123");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
