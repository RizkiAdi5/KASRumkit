"use server";

import { AuthError } from "next-auth";
import { signIn } from "@/lib/auth";

export async function loginAction(_prevState: string | undefined, formData: FormData) {
  try {
    await signIn("credentials", {
      username: formData.get("username"),
      password: formData.get("password"),
      redirectTo: "/dashboard",
    });
  } catch (error) {
    if (error instanceof AuthError) {
      return "Username atau password salah.";
    }
    throw error;
  }
}
