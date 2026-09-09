import type { NextConfig } from "next";

function apiImagePattern(): {
  protocol: "http" | "https";
  hostname: string;
  port?: string;
  pathname: "/storage/**";
} {
  const raw = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";
  try {
    const u = new URL(raw);
    return {
      protocol: u.protocol === "https:" ? "https" : "http",
      hostname: u.hostname || "localhost",
      ...(u.port ? { port: u.port } : {}),
      pathname: "/storage/**",
    };
  } catch {
    return {
      protocol: "http",
      hostname: "localhost",
      port: "8000",
      pathname: "/storage/**",
    };
  }
}

const nextConfig: NextConfig = {
  // Docker/compose often leaves root/nobody-owned `.next/`; keep a local-writable dist.
  distDir: process.env.NEXT_DIST_DIR || ".next-local",
  images: {
    remotePatterns: [apiImagePattern()],
  },
};

export default nextConfig;
