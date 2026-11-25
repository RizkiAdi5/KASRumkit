"use server";

import { z } from "zod";
import bcrypt from "bcryptjs";
import { revalidatePath } from "next/cache";
import { prisma } from "@/lib/prisma";
import { requireRoleAction } from "@/lib/rbac";

export type ActionResult = { ok: boolean; error?: string };

const createSchema = z
  .object({
    username: z.string().min(3, "Username minimal 3 karakter"),
    namaLengkap: z.string().min(1, "Nama lengkap wajib diisi"),
    email: z.string().email("Email tidak valid"),
    role: z.enum(["ADMIN", "SUPERVISOR", "KASIR"]),
    password: z.string().min(6, "Password minimal 6 karakter"),
    confirmPassword: z.string(),
  })
  .refine((d) => d.password === d.confirmPassword, {
    message: "Konfirmasi password tidak cocok",
    path: ["confirmPassword"],
  });

export async function createUserAction(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  await requireRoleAction(["ADMIN"]);

  const parsed = createSchema.safeParse({
    username: formData.get("username"),
    namaLengkap: formData.get("namaLengkap"),
    email: formData.get("email"),
    role: formData.get("role"),
    password: formData.get("password"),
    confirmPassword: formData.get("confirmPassword"),
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const existing = await prisma.user.findFirst({
    where: { OR: [{ username: parsed.data.username }, { email: parsed.data.email }] },
  });
  if (existing) return { ok: false, error: "Username atau email sudah digunakan." };

  const passwordHash = await bcrypt.hash(parsed.data.password, 10);
  await prisma.user.create({
    data: {
      username: parsed.data.username,
      namaLengkap: parsed.data.namaLengkap,
      email: parsed.data.email,
      role: parsed.data.role,
      passwordHash,
    },
  });

  revalidatePath("/users");
  return { ok: true };
}

const updateSchema = z.object({
  id: z.coerce.number(),
  namaLengkap: z.string().min(1, "Nama lengkap wajib diisi"),
  email: z.string().email("Email tidak valid"),
  role: z.enum(["ADMIN", "SUPERVISOR", "KASIR"]),
  isActive: z.boolean().optional(),
});

export async function updateUserAction(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  await requireRoleAction(["ADMIN"]);

  const parsed = updateSchema.safeParse({
    id: formData.get("id"),
    namaLengkap: formData.get("namaLengkap"),
    email: formData.get("email"),
    role: formData.get("role"),
    isActive: formData.get("isActive") === "on",
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const existingEmail = await prisma.user.findFirst({
    where: { email: parsed.data.email, NOT: { id: parsed.data.id } },
  });
  if (existingEmail) return { ok: false, error: "Email sudah digunakan oleh pengguna lain." };

  await prisma.user.update({
    where: { id: parsed.data.id },
    data: {
      namaLengkap: parsed.data.namaLengkap,
      email: parsed.data.email,
      role: parsed.data.role,
      isActive: !!parsed.data.isActive,
    },
  });

  revalidatePath("/users");
  return { ok: true };
}

const passwordSchema = z
  .object({
    id: z.coerce.number(),
    password: z.string().min(6, "Password minimal 6 karakter"),
    confirmPassword: z.string(),
  })
  .refine((d) => d.password === d.confirmPassword, {
    message: "Konfirmasi password tidak cocok",
    path: ["confirmPassword"],
  });

export async function changePasswordAction(_prev: ActionResult, formData: FormData): Promise<ActionResult> {
  await requireRoleAction(["ADMIN"]);

  const parsed = passwordSchema.safeParse({
    id: formData.get("id"),
    password: formData.get("password"),
    confirmPassword: formData.get("confirmPassword"),
  });
  if (!parsed.success) {
    return { ok: false, error: parsed.error.issues[0]?.message ?? "Data tidak valid." };
  }

  const passwordHash = await bcrypt.hash(parsed.data.password, 10);
  await prisma.user.update({ where: { id: parsed.data.id }, data: { passwordHash } });
  return { ok: true };
}

export async function deleteUserAction(id: number) {
  const current = await requireRoleAction(["ADMIN"]);
  if (Number(current.id) === id) {
    throw new Error("Tidak dapat menghapus akun sendiri.");
  }
  await prisma.user.delete({ where: { id } });
  revalidatePath("/users");
}
