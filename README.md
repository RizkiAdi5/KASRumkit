# KAS Ruang Gelatik — Sistem Kas Rumah Sakit

Next.js rewrite of the original PHP app (kept for reference in `legacy-php/`). Cash transaction ledger for a hospital: pemasukan/pengeluaran tracking, category & employee master data, reporting with Excel export, and role-based access control.

## Stack

- Next.js 16 (App Router, TypeScript, Server Actions)
- Prisma 6 + PostgreSQL
- Auth.js (NextAuth v5) — credentials login, JWT sessions
- Tailwind CSS v4
- recharts (monthly chart), sonner (toasts), exceljs (real `.xlsx` export)

## Local setup

1. `npm install`
2. Point `DATABASE_URL` in `.env` at a Postgres database (a throwaway local one works fine: `docker run -d -e POSTGRES_PASSWORD=postgres -e POSTGRES_DB=kas_rumkit -p 5433:5432 postgres:16`, then `DATABASE_URL="postgresql://postgres:postgres@localhost:5433/kas_rumkit?schema=public"`).
3. `npx prisma migrate dev` — creates the schema.
4. `npx prisma db seed` — creates the admin user and default categories.
5. `npm run dev` — http://localhost:3000

Default login: **admin / admin123** (change this immediately in a real deployment, via the Pengguna page or directly in the database).

## Deploying (Vercel + Neon/Supabase)

1. Create a Postgres database on Neon or Supabase, copy its connection string.
2. Push this repo to GitHub, import it into Vercel.
3. Set environment variables in Vercel: `DATABASE_URL`, `NEXTAUTH_SECRET` (any random 32+ char string — `openssl rand -base64 32`), `NEXTAUTH_URL` (your production URL).
4. Vercel runs `npm run build`; run `npx prisma migrate deploy` once against the production database (locally, with `DATABASE_URL` pointed at prod, or via a one-off Vercel deploy hook) before first use, then `npx prisma db seed` for the initial admin account.

## Roles

| Page | Admin | Supervisor | Kasir |
|---|---|---|---|
| Dashboard | ✓ | ✓ | ✓ |
| Transaksi — input | ✓ | ✓ | ✓ |
| Transaksi — lihat/hapus | semua | semua | **milik sendiri** |
| Laporan + Export Excel | ✓ | ✓ | ✗ |
| Kategori | ✓ | ✓ | ✗ |
| Pegawai | ✓ | ✓ | ✗ |
| Pengguna | ✓ | ✗ | ✗ |

## Notable differences from the old PHP app

- Real role enforcement (the PHP version only gated the Users page; everything else was open to any logged-in user regardless of role).
- "Kas Bulanan Pegawai" is now a boolean flag on the category (`isPegawaiBulanan`) instead of matching the category name string in three different places.
- Excel export is a genuine `.xlsx` file (the PHP version was an HTML table renamed `.xls`).
- `saldo_kas` table dropped — it existed in the old schema but was never read or written anywhere.
- Money uses Prisma `Decimal` instead of PHP floats.

## Not included

- No automated migration of existing MySQL data into the new Postgres schema. If production data needs to be carried over, that's a one-off export/import script to write separately.
- No password reset, rate limiting, or CSRF tokens — none existed in the original app either; add if needed.

## Known transitive dependency advisories

`npm audit` flags a handful of moderate/high advisories in transitive dependencies of `prisma` (CLI-only, not bundled in the running app) and `exceljs`'s `uuid` dependency. Fixing them requires a breaking major-version bump of those packages; left as-is for now since none are exploitable in this app's actual usage. Re-check with `npm audit` before a production deploy and decide whether to take the breaking upgrade at that point.
