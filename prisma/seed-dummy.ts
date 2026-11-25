import { PrismaClient } from "@prisma/client";
import { generateNomorTransaksi } from "../lib/format";

const prisma = new PrismaClient();

function randomInt(min: number, max: number) {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

function pick<T>(arr: T[]): T {
  return arr[randomInt(0, arr.length - 1)];
}

async function main() {
  const users = await prisma.user.findMany();
  const kategoriList = await prisma.kategori.findMany({ where: { isActive: true } });
  const kasBulanan = kategoriList.find((k) => k.isPegawaiBulanan)!;
  const pemasukanLain = kategoriList.filter((k) => k.tipe === "PEMASUKAN" && !k.isPegawaiBulanan);
  const pengeluaran = kategoriList.filter((k) => k.tipe === "PENGELUARAN");

  const pegawaiSeed = [
    { namaLengkap: "Dr. Ahmad Rizki", departemen: "Poli Umum" },
    { namaLengkap: "Suster Siti Nurhaliza", departemen: "IGD" },
    { namaLengkap: "Ahmad Fauzi", departemen: "Administrasi" },
    { namaLengkap: "Dr. Sarah Putri", departemen: "Poli Gigi" },
    { namaLengkap: "Budi Santoso", departemen: "Laboratorium" },
  ];

  const pegawaiList = [];
  for (const p of pegawaiSeed) {
    const existing = await prisma.pegawai.findFirst({ where: { namaLengkap: p.namaLengkap } });
    pegawaiList.push(existing ?? (await prisma.pegawai.create({ data: p })));
  }

  const keteranganPemasukan = [
    "Pembayaran pasien rawat jalan",
    "Pembayaran tindakan medis",
    "Pembayaran obat & resep",
    "Klaim BPJS",
    "Pembayaran tunai pasien",
  ];
  const keteranganPengeluaran = [
    "Pembelian alat kesehatan",
    "Tagihan listrik bulanan",
    "Tagihan air bulanan",
    "Pembelian ATK",
    "Pemeliharaan AC ruangan",
    "Service genset",
  ];

  const now = new Date();
  const monthsBack = 6;
  let created = 0;

  for (let m = monthsBack; m >= 0; m--) {
    const monthDate = new Date(now.getFullYear(), now.getMonth() - m, 1);
    const daysInMonth = m === 0 ? now.getDate() : new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();

    // 8-14 pemasukan umum per bulan
    const pemasukanCount = randomInt(8, 14);
    for (let i = 0; i < pemasukanCount; i++) {
      const day = randomInt(1, daysInMonth);
      const tanggal = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
      await prisma.transaksi.create({
        data: {
          nomorTransaksi: generateNomorTransaksi("PEMASUKAN") + "-" + created,
          tanggalTransaksi: tanggal,
          kategoriId: pick(pemasukanLain).id,
          tipeTransaksi: "PEMASUKAN",
          jumlah: randomInt(150, 5000) * 1000,
          keterangan: pick(keteranganPemasukan),
          userId: pick(users).id,
        },
      });
      created++;
    }

    // 5-8 pengeluaran per bulan
    const pengeluaranCount = randomInt(5, 8);
    for (let i = 0; i < pengeluaranCount; i++) {
      const day = randomInt(1, daysInMonth);
      const tanggal = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
      await prisma.transaksi.create({
        data: {
          nomorTransaksi: generateNomorTransaksi("PENGELUARAN") + "-" + created,
          tanggalTransaksi: tanggal,
          kategoriId: pick(pengeluaran).id,
          tipeTransaksi: "PENGELUARAN",
          jumlah: randomInt(200, 8000) * 1000,
          keterangan: pick(keteranganPengeluaran),
          userId: pick(users).id,
        },
      });
      created++;
    }

    // kas bulanan pegawai: setiap pegawai setor sekali per bulan
    for (const pegawai of pegawaiList) {
      const day = randomInt(1, Math.min(10, daysInMonth));
      const tanggal = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
      await prisma.transaksi.create({
        data: {
          nomorTransaksi: generateNomorTransaksi("PEMASUKAN") + "-" + created,
          tanggalTransaksi: tanggal,
          kategoriId: kasBulanan.id,
          tipeTransaksi: "PEMASUKAN",
          jumlah: pick([50000, 75000, 100000]),
          keterangan: "Setoran kas bulanan",
          userId: pick(users).id,
          pegawaiId: pegawai.id,
        },
      });
      created++;
    }
  }

  console.log(`Dummy data created: ${created} transaksi, ${pegawaiList.length} pegawai.`);
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
