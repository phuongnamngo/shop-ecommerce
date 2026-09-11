<?php

namespace App\Services\Promotion;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class FlashSalePricingService
{
    /**
     * @param  list<int>  $variantIds
     * @return Collection<int, FlashSaleItem>
     */
    public function offersForVariants(array $variantIds, ?CarbonInterface $at = null): Collection
    {
        $at ??= now();
        $variantIds = array_values(array_unique(array_filter($variantIds)));
        if ($variantIds === []) {
            return collect();
        }

        $items = FlashSaleItem::query()
            ->select('flash_sale_items.*')
            ->join('flash_sales', 'flash_sales.id', '=', 'flash_sale_items.flash_sale_id')
            ->whereIn('flash_sale_items.product_variant_id', $variantIds)
            ->whereNull('flash_sales.deleted_at')
            ->where('flash_sales.status', FlashSale::STATUS_ACTIVE)
            ->where('flash_sales.starts_at', '<=', $at)
            ->where('flash_sales.ends_at', '>=', $at)
            ->orderBy('flash_sale_items.sale_price')
            ->orderBy('flash_sales.starts_at')
            ->orderBy('flash_sale_items.id')
            ->with('flashSale')
            ->get();

        return $items->unique('product_variant_id')->keyBy('product_variant_id');
    }

    public function offerForVariant(int $variantId, ?CarbonInterface $at = null): ?FlashSaleItem
    {
        return $this->offersForVariants([$variantId], $at)->get($variantId);
    }

    public function unitPrice(ProductVariant $variant, ?CarbonInterface $at = null): string
    {
        $offer = $this->offerForVariant($variant->id, $at);

        return $offer?->sale_price ?? (string) $variant->price;
    }
}
