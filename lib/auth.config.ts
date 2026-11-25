import type { NextAuthConfig } from "next-auth";
import type { Role } from "@prisma/client";
import { NextResponse } from "next/server";

export const authConfig: NextAuthConfig = {
  pages: { signIn: "/login" },
  session: { strategy: "jwt" },
  providers: [],
  callbacks: {
    authorized({ auth, request }) {
      const isLoggedIn = !!auth?.user;
      const isLoginPage = request.nextUrl.pathname === "/login";

      if (isLoginPage) {
        if (isLoggedIn) {
          return NextResponse.redirect(new URL("/dashboard", request.nextUrl));
        }
        return true;
      }

      return isLoggedIn;
    },
    jwt({ token, user }) {
      if (user) {
        token.id = user.id as string;
        token.role = user.role;
        token.username = user.username;
      }
      return token;
    },
    session({ session, token }) {
      session.user.id = token.id as string;
      session.user.role = token.role as Role;
      session.user.username = token.username as string;
      return session;
    },
  },
};
