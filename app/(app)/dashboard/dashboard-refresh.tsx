"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";

export function DashboardRefresh() {
  const router = useRouter();

  useEffect(() => {
    const interval = setInterval(() => router.refresh(), 5 * 60 * 1000);
    return () => clearInterval(interval);
  }, [router]);

  return null;
}
