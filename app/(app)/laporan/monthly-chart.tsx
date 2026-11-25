"use client";

import { BarChart, Bar, XAxis, YAxis, Tooltip, Legend, ResponsiveContainer, CartesianGrid, LabelList } from "recharts";
import { formatRupiah } from "@/lib/format";

// Validated via dataviz skill's validate_palette.js (light mode, categorical):
// emerald-600 / red-600 — all checks pass (CVD ΔE 8.6, contrast >= 3:1).
const COLOR_PEMASUKAN = "#059669";
const COLOR_PENGELUARAN = "#dc2626";

function compactRupiah(label: unknown): string {
  const value = Number(label ?? 0);
  if (!value) return "";
  if (Math.abs(value) >= 1_000_000) return `${(value / 1_000_000).toFixed(1).replace(/\.0$/, "")}jt`;
  if (Math.abs(value) >= 1_000) return `${Math.round(value / 1_000)}rb`;
  return String(value);
}

export function MonthlyChart({
  data,
}: {
  data: { label: string; Pemasukan: number; Pengeluaran: number }[];
}) {
  if (data.length === 0) {
    return <p className="py-16 text-center text-sm text-slate-400">Tidak ada data untuk periode ini.</p>;
  }

  return (
    <ResponsiveContainer width="100%" height={300}>
      <BarChart data={data} margin={{ top: 16, right: 8, left: 0, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
        <XAxis dataKey="label" tick={{ fontSize: 12, fill: "#64748b" }} stroke="#cbd5e1" />
        <YAxis tickFormatter={(v: number) => formatRupiah(v)} tick={{ fontSize: 11, fill: "#64748b" }} stroke="#cbd5e1" width={90} />
        <Tooltip
          formatter={(v) => formatRupiah(Number(v))}
          contentStyle={{ borderRadius: 8, borderColor: "#e2e8f0", fontSize: 13 }}
        />
        <Legend wrapperStyle={{ fontSize: 13, paddingTop: 8 }} />
        <Bar dataKey="Pemasukan" fill={COLOR_PEMASUKAN} radius={[4, 4, 0, 0]} maxBarSize={40}>
          <LabelList dataKey="Pemasukan" position="top" formatter={compactRupiah} style={{ fontSize: 10, fill: "#475569" }} />
        </Bar>
        <Bar dataKey="Pengeluaran" fill={COLOR_PENGELUARAN} radius={[4, 4, 0, 0]} maxBarSize={40}>
          <LabelList dataKey="Pengeluaran" position="top" formatter={compactRupiah} style={{ fontSize: 10, fill: "#475569" }} />
        </Bar>
      </BarChart>
    </ResponsiveContainer>
  );
}
