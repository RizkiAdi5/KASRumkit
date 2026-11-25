import { prisma } from "@/lib/prisma";
import { requireRole } from "@/lib/rbac";
import { UsersManager } from "./users-manager";

const PAGE_SIZE = 10;

export default async function UsersPage({
  searchParams,
}: {
  searchParams: Promise<{ page?: string }>;
}) {
  const currentUser = await requireRole(["ADMIN"]);
  const { page: pageParam } = await searchParams;
  const page = Math.max(1, Number(pageParam) || 1);

  const [total, users] = await Promise.all([
    prisma.user.count(),
    prisma.user.findMany({
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
    }),
  ]);

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  return <UsersManager users={users} page={page} totalPages={totalPages} currentUserId={Number(currentUser.id)} />;
}
