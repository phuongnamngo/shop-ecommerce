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

function allowLocalApiImages(): boolean {
  if (process.env.NEXT_IMAGE_ALLOW_LOCAL_IP === "true") return true;
  if (process.env.NEXT_IMAGE_ALLOW_LOCAL_IP === "false") return false;
  if (process.env.NODE_ENV !== "production") return true;

  const raw =
    process.env.NEXT_PUBLIC_API_URL ?? process.env.API_URL ?? "http://localhost:8000";
  try {
    const host = new URL(raw).hostname;
    return host === "localhost" || host === "127.0.0.1" || host === "api";
  } catch {
    return false;
  }
}

const nextConfig: NextConfig = {
  // Docker/compose often leaves root/nobody-owned `.next/`; keep a local-writable dist.
  distDir: process.env.NEXT_DIST_DIR || ".next-local",
  images: {
    remotePatterns: [apiImagePattern()],
    // Next 16 blocks optimizer fetches to loopback/private IPs (SSRF guard).
    // Laravel /storage lives on the local API in docker/dev.
    dangerouslyAllowLocalIP: allowLocalApiImages(),
  },
};

export default nextConfig;
