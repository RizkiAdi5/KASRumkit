"use client";

import { useActionState, useEffect, useState } from "react";
import { toast } from "sonner";
import type { User } from "@prisma/client";
import { Plus, Pencil, KeyRound, Trash2, UserCog } from "lucide-react";
import { Modal } from "@/components/ui/modal";
import { Button } from "@/components/ui/button";
import { Input, Select, Label } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Pagination } from "@/components/ui/pagination";
import { Card } from "@/components/ui/card";
import { PageHeader } from "@/components/ui/page-header";
import { EmptyRow } from "@/components/ui/empty-state";
import { ErrorMessage } from "@/components/ui/error-message";
import {
  createUserAction,
  updateUserAction,
  changePasswordAction,
  deleteUserAction,
  type ActionResult,
} from "./actions";

const initialState: ActionResult = { ok: false };
const roleLabel: Record<string, string> = { ADMIN: "Admin", SUPERVISOR: "Supervisor", KASIR: "Kasir" };

export function UsersManager({
  users,
  page,
  totalPages,
  currentUserId,
}: {
  users: User[];
  page: number;
  totalPages: number;
  currentUserId: number;
}) {
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<User | null>(null);
  const [pwUser, setPwUser] = useState<User | null>(null);

  const [createState, createFormAction, createPending] = useActionState(createUserAction, initialState);
  const [updateState, updateFormAction, updatePending] = useActionState(updateUserAction, initialState);
  const [pwState, pwFormAction, pwPending] = useActionState(changePasswordAction, initialState);

  useEffect(() => {
    if (createState.ok) {
      toast.success("Pengguna berhasil ditambahkan.");
      setFormOpen(false);
    }
  }, [createState]);

  useEffect(() => {
    if (updateState.ok) {
      toast.success("Pengguna berhasil diperbarui.");
      setFormOpen(false);
      setEditing(null);
    }
  }, [updateState]);

  useEffect(() => {
    if (pwState.ok) {
      toast.success("Password berhasil diubah.");
      setPwUser(null);
    }
  }, [pwState]);

  async function handleDelete(u: User) {
    if (!confirm(`Hapus pengguna "${u.namaLengkap}"?`)) return;
    try {
      await deleteUserAction(u.id);
      toast.success("Pengguna dihapus.");
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Gagal menghapus pengguna.");
    }
  }

  return (
    <div className="space-y-6">
      <PageHeader
        title="Pengguna"
        description="Kelola akun pengguna sistem (khusus admin)."
        action={
          <Button
            onClick={() => {
              setEditing(null);
              setFormOpen(true);
            }}
          >
            <Plus className="h-4 w-4" />
            Tambah Pengguna
          </Button>
        }
      />

      <Card>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-slate-100 bg-slate-50/60 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <th className="px-5 py-3">Username</th>
                <th className="px-5 py-3">Nama Lengkap</th>
                <th className="px-5 py-3">Email</th>
                <th className="px-5 py-3">Role</th>
                <th className="px-5 py-3">Status</th>
                <th className="px-5 py-3" />
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id} className="border-b border-slate-50 transition-colors last:border-0 hover:bg-slate-50/60">
                  <td className="px-5 py-3 font-medium text-slate-900">{u.username}</td>
                  <td className="px-5 py-3 text-slate-700">{u.namaLengkap}</td>
                  <td className="px-5 py-3 text-slate-500">{u.email}</td>
                  <td className="px-5 py-3">
                    <Badge tone="blue">{roleLabel[u.role]}</Badge>
                  </td>
                  <td className="px-5 py-3">
                    <Badge tone={u.isActive ? "green" : "slate"}>{u.isActive ? "Aktif" : "Nonaktif"}</Badge>
                  </td>
                  <td className="px-5 py-3 text-right">
                    <div className="flex items-center justify-end gap-1">
                      <button
                        onClick={() => {
                          setEditing(u);
                          setFormOpen(true);
                        }}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                        aria-label="Edit pengguna"
                      >
                        <Pencil className="h-4 w-4" />
                      </button>
                      <button
                        onClick={() => setPwUser(u)}
                        className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                        aria-label="Ubah password"
                      >
                        <KeyRound className="h-4 w-4" />
                      </button>
                      {u.id !== currentUserId && (
                        <button
                          onClick={() => handleDelete(u)}
                          className="rounded-md p-1.5 text-slate-400 transition-colors hover:bg-red-50 hover:text-red-600"
                          aria-label="Hapus pengguna"
                        >
                          <Trash2 className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
              {users.length === 0 && <EmptyRow colSpan={6} icon={UserCog} message="Belum ada pengguna." />}
            </tbody>
          </table>
        </div>
        <div className="border-t border-slate-100 px-5 py-3">
          <Pagination page={page} totalPages={totalPages} basePath="/users" searchParams={{}} />
        </div>
      </Card>

      <Modal open={formOpen} onClose={() => setFormOpen(false)} title={editing ? "Edit Pengguna" : "Tambah Pengguna"}>
        {editing ? (
          <form key={editing.id} action={updateFormAction} className="space-y-4">
            <input type="hidden" name="id" value={editing.id} />
            <div>
              <Label htmlFor="namaLengkap">Nama Lengkap</Label>
              <Input id="namaLengkap" name="namaLengkap" required defaultValue={editing.namaLengkap} />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input id="email" name="email" type="email" required defaultValue={editing.email} />
            </div>
            <div>
              <Label htmlFor="role">Role</Label>
              <Select id="role" name="role" required defaultValue={editing.role}>
                <option value="ADMIN">Admin</option>
                <option value="SUPERVISOR">Supervisor</option>
                <option value="KASIR">Kasir</option>
              </Select>
            </div>
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                name="isActive"
                defaultChecked={editing.isActive}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30"
              />
              Akun aktif
            </label>
            {updateState.error && <ErrorMessage>{updateState.error}</ErrorMessage>}
            <div className="flex justify-end gap-2 pt-2">
              <Button type="button" variant="secondary" onClick={() => setFormOpen(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={updatePending}>
                {updatePending ? "Menyimpan..." : "Simpan"}
              </Button>
            </div>
          </form>
        ) : (
          <form action={createFormAction} className="space-y-4">
            <div>
              <Label htmlFor="username">Username</Label>
              <Input id="username" name="username" required minLength={3} />
            </div>
            <div>
              <Label htmlFor="namaLengkap">Nama Lengkap</Label>
              <Input id="namaLengkap" name="namaLengkap" required />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input id="email" name="email" type="email" required />
            </div>
            <div>
              <Label htmlFor="role">Role</Label>
              <Select id="role" name="role" required defaultValue="KASIR">
                <option value="ADMIN">Admin</option>
                <option value="SUPERVISOR">Supervisor</option>
                <option value="KASIR">Kasir</option>
              </Select>
            </div>
            <div>
              <Label htmlFor="password">Password</Label>
              <Input id="password" name="password" type="password" required minLength={6} />
            </div>
            <div>
              <Label htmlFor="confirmPassword">Konfirmasi Password</Label>
              <Input id="confirmPassword" name="confirmPassword" type="password" required minLength={6} />
            </div>
            {createState.error && <ErrorMessage>{createState.error}</ErrorMessage>}
            <div className="flex justify-end gap-2 pt-2">
              <Button type="button" variant="secondary" onClick={() => setFormOpen(false)}>
                Batal
              </Button>
              <Button type="submit" disabled={createPending}>
                {createPending ? "Menyimpan..." : "Simpan"}
              </Button>
            </div>
          </form>
        )}
      </Modal>

      <Modal open={!!pwUser} onClose={() => setPwUser(null)} title={`Ubah Password - ${pwUser?.username ?? ""}`}>
        <form key={pwUser?.id} action={pwFormAction} className="space-y-4">
          <input type="hidden" name="id" value={pwUser?.id} />
          <div>
            <Label htmlFor="password">Password Baru</Label>
            <Input id="password" name="password" type="password" required minLength={6} />
          </div>
          <div>
            <Label htmlFor="confirmPassword">Konfirmasi Password</Label>
            <Input id="confirmPassword" name="confirmPassword" type="password" required minLength={6} />
          </div>
          {pwState.error && <ErrorMessage>{pwState.error}</ErrorMessage>}
          <div className="flex justify-end gap-2 pt-2">
            <Button type="button" variant="secondary" onClick={() => setPwUser(null)}>
              Batal
            </Button>
            <Button type="submit" disabled={pwPending}>
              {pwPending ? "Menyimpan..." : "Simpan"}
            </Button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
