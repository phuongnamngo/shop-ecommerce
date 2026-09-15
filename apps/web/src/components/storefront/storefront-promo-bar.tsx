import Link from "next/link";

import type { PublicCmsBanner } from "@/lib/api/storefront/cms";

export function StorefrontPromoBar({
  promo,
}: {
  promo: PublicCmsBanner | null;
}) {
  if (!promo) {
    return null;
  }

  return (
    <div className="bg-slate-950 text-white">
      <div className="mx-auto flex max-w-[1320px] items-center justify-center gap-3 px-4 py-2 text-center text-xs sm:text-sm">
        <span>{promo.title}</span>
        {promo.link_url ? (
          <Link
            href={promo.link_url}
            className="hidden font-semibold text-blue-300 underline-offset-2 hover:underline sm:inline"
          >
            Mua ngay
          </Link>
        ) : null}
      </div>
    </div>
  );
}
