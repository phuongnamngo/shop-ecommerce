import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: ["/", "/products"],
      disallow: [
        "/admin",
        "/admin/",
        "/cart",
        "/checkout",
        "/checkout/result",
        "/login",
        "/register",
        "/forgot-password",
        "/account",
        "/account/",
        "/reset-password",
      ],
    },
  };
}
