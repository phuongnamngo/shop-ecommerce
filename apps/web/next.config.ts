import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Docker/compose often leaves root/nobody-owned `.next/`; keep a local-writable dist.
  distDir: process.env.NEXT_DIST_DIR || ".next-local",
};

export default nextConfig;
