<?php

namespace App\Http\Resources\Engagement;

use App\Models\WishlistItem;
use App\Services\Promotion\FlashSalePricingService;
use App\Support\CatalogImagePath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WishlistItem */
final class WishlistItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->relationLoaded('variant') ? $this->variant : null;
        $product = $variant?->relationLoaded('product') ? $variant->product : null;
        $price = $variant === null
            ? null
            : app(FlashSalePricingService::class)->unitPrice($variant);

        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product' => $product === null ? null : [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'sku' => $variant?->sku,
            'price' => $price,
            'thumbnail' => $this->thumbnail(),
        ];
    }

    /**
     * @return array{url: string, thumbnail_url: string}|null
     */
    private function thumbnail(): ?array
    {
        $variant = $this->relationLoaded('variant') ? $this->variant : null;
        $product = $variant?->relationLoaded('product') ? $variant->product : null;
        $image = null;
        if ($variant !== null && $variant->relationLoaded('images')) {
            $image = $variant->images->sortBy('position')->first();
        }
        if ($image === null && $product !== null && $product->relationLoaded('images')) {
            $image = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        }
        if ($image === null || $image->path === null || $image->path === '') {
            return null;
        }

        return [
            'url' => CatalogImagePath::url($image->path),
            'thumbnail_url' => CatalogImagePath::url(CatalogImagePath::thumbnailPath($image->path)),
        ];
    }
}
