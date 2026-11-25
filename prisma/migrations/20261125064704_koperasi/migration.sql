-- CreateTable
CREATE TABLE "koperasi_produk" (
    "id" SERIAL NOT NULL,
    "namaProduk" TEXT NOT NULL,
    "hargaJual" DECIMAL(15,2),
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "koperasi_produk_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "koperasi_transaksi" (
    "id" SERIAL NOT NULL,
    "nomorTransaksi" TEXT NOT NULL,
    "tanggalTransaksi" DATE NOT NULL,
    "tipeTransaksi" "TipeTransaksi" NOT NULL,
    "produkId" INTEGER,
    "jumlah" DECIMAL(15,2) NOT NULL,
    "keterangan" TEXT,
    "userId" INTEGER NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "koperasi_transaksi_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "koperasi_transaksi_nomorTransaksi_key" ON "koperasi_transaksi"("nomorTransaksi");

-- AddForeignKey
ALTER TABLE "koperasi_transaksi" ADD CONSTRAINT "koperasi_transaksi_produkId_fkey" FOREIGN KEY ("produkId") REFERENCES "koperasi_produk"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "koperasi_transaksi" ADD CONSTRAINT "koperasi_transaksi_userId_fkey" FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE CASCADE;
