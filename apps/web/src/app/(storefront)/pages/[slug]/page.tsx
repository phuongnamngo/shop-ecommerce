import type { Metadata } from "next";
import { notFound } from "next/navigation";

import { getPublicCmsPage } from "@/lib/api/storefront/cms";
import { StorefrontApiError } from "@/lib/api/storefront/client";
import { sfContainer } from "@/lib/storefront/ui";

export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ slug: string }>;
}): Promise<Metadata> {
  const { slug } = await params;
  try {
    const page = await getPublicCmsPage(slug);
    return { title: page.title };
  } catch {
    return { title: "Watch" };
  }
}

export default async function StorefrontCmsPage({
  params,
}: {
  params: Promise<{ slug: string }>;
}) {
  const { slug } = await params;
  let page;
  try {
    page = await getPublicCmsPage(slug);
  } catch (error) {
    if (error instanceof StorefrontApiError && error.status === 404) {
      notFound();
    }
    throw error;
  }

  return (
    <main className={`${sfContainer} py-12`}>
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">
        {page.title}
      </h1>
      <article
        className="prose prose-slate mt-8 max-w-none"
        dangerouslySetInnerHTML={{ __html: page.body_html }}
      />
    </main>
  );
}
