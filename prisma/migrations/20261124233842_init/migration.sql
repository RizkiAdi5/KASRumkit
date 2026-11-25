-- CreateEnum
CREATE TYPE "Role" AS ENUM ('ADMIN', 'SUPERVISOR', 'KASIR');

-- CreateEnum
CREATE TYPE "TipeTransaksi" AS ENUM ('PEMASUKAN', 'PENGELUARAN');

-- CreateTable
CREATE TABLE "users" (
    "id" SERIAL NOT NULL,
    "username" TEXT NOT NULL,
    "passwordHash" TEXT NOT NULL,
    "namaLengkap" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "role" "Role" NOT NULL DEFAULT 'KASIR',
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "users_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "kategori_transaksi" (
    "id" SERIAL NOT NULL,
    "namaKategori" TEXT NOT NULL,
    "tipe" "TipeTransaksi" NOT NULL,
    "deskripsi" TEXT,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "isPegawaiBulanan" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "kategori_transaksi_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "pegawai" (
    "id" SERIAL NOT NULL,
    "namaLengkap" TEXT NOT NULL,
    "departemen" TEXT,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "pegawai_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "transaksi_kas" (
    "id" SERIAL NOT NULL,
    "nomorTransaksi" TEXT NOT NULL,
    "tanggalTransaksi" DATE NOT NULL,
    "kategoriId" INTEGER NOT NULL,
    "tipeTransaksi" "TipeTransaksi" NOT NULL,
    "jumlah" DECIMAL(15,2) NOT NULL,
    "keterangan" TEXT,
    "userId" INTEGER NOT NULL,
    "pegawaiId" INTEGER,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "transaksi_kas_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "users_username_key" ON "users"("username");

-- CreateIndex
CREATE UNIQUE INDEX "users_email_key" ON "users"("email");

-- CreateIndex
CREATE UNIQUE INDEX "transaksi_kas_nomorTransaksi_key" ON "transaksi_kas"("nomorTransaksi");

-- AddForeignKey
ALTER TABLE "transaksi_kas" ADD CONSTRAINT "transaksi_kas_kategoriId_fkey" FOREIGN KEY ("kategoriId") REFERENCES "kategori_transaksi"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "transaksi_kas" ADD CONSTRAINT "transaksi_kas_userId_fkey" FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "transaksi_kas" ADD CONSTRAINT "transaksi_kas_pegawaiId_fkey" FOREIGN KEY ("pegawaiId") REFERENCES "pegawai"("id") ON DELETE SET NULL ON UPDATE CASCADE;
