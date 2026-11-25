export function formatRupiah(value: number | string): string {
  const n = typeof value === "string" ? parseFloat(value) : value;
  return "Rp " + n.toLocaleString("id-ID", { maximumFractionDigits: 0 });
}

// tanggalTransaksi is stored as a @db.Date, which Prisma round-trips as a
// UTC-midnight instant regardless of server timezone — always read it back
// in UTC so the displayed day never drifts with where the process runs.
export function formatTanggal(date: Date | string): string {
  const d = typeof date === "string" ? new Date(date) : date;
  return d.toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    timeZone: "UTC",
  });
}

// "Today" in the user's own calendar, for date-input defaults. Deliberately
// NOT toISOString().slice(0,10) — that converts through UTC and rolls back a
// day for anyone west of... anyone *ahead* of UTC (e.g. WIB, UTC+7) during
// the first hours of their local day.
export function todayLocalISODate(): string {
  return new Intl.DateTimeFormat("en-CA").format(new Date());
}

export function generateNomorTransaksi(tipe: "PEMASUKAN" | "PENGELUARAN", modulePrefix = ""): string {
  const prefix = (modulePrefix ? `${modulePrefix}-` : "") + (tipe === "PEMASUKAN" ? "IN" : "OUT");
  const date = todayLocalISODate().replace(/-/g, "");
  const random = Math.random().toString(16).slice(2, 6).toUpperCase();
  return `${prefix}${date}${random}`;
}

export const BULAN_INDONESIA = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];
