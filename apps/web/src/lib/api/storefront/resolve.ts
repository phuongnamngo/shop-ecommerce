import { listAllPublicBrands } from "@/lib/api/storefront/catalog";
import type {
  PublicBrand,
  PublicCategoryNode,
} from "@/lib/api/storefront/types";

export function findCategoryBySlug(
  nodes: PublicCategoryNode[],
  slug: string,
): PublicCategoryNode | null {
  for (const node of nodes) {
    if (node.slug === slug) return node;
    const nested = findCategoryBySlug(node.children ?? [], slug);
    if (nested) return nested;
  }
  return null;
}

export function leafCategories(
  nodes: PublicCategoryNode[],
): PublicCategoryNode[] {
  return nodes.flatMap((node) =>
    node.children && node.children.length > 0 ? node.children : [node],
  );
}

export async function findBrandBySlug(
  slug: string,
): Promise<PublicBrand | null> {
  const brands = await listAllPublicBrands();
  return brands.find((brand) => brand.slug === slug) ?? null;
}
