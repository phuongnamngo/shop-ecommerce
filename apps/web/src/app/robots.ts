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
        "/account",
        "/reset-password",
      ],
    },
  };
}
