import { redirect } from "next/navigation";
import type { Role } from "@prisma/client";
import { auth } from "@/lib/auth";

export async function requireUser() {
  const session = await auth();
  if (!session?.user) redirect("/login");
  return session.user;
}

/** For Server Components / pages: redirects away if role isn't allowed. */
export async function requireRole(roles: Role[]) {
  const user = await requireUser();
  if (!roles.includes(user.role)) redirect("/dashboard");
  return user;
}

/** For Server Actions: throws instead of redirecting (the UI should already hide the entry point). */
export async function requireRoleAction(roles: Role[]) {
  const session = await auth();
  if (!session?.user) throw new Error("Unauthorized");
  if (!roles.includes(session.user.role)) throw new Error("Forbidden");
  return session.user;
}
