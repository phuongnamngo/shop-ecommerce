<?php

namespace App\Http\Resources\Cart;

use App\Models\CartItem;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use App\Support\CatalogImagePath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartItem */
final class CartItemResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     product_variant_id: int,
     *     qty: int,
     *     unit_price: string,
     *     line_total: string,
     *     product: array{name: string, slug: string}|null,
     *     sku: string|null,
     *     attributes: list<array{id: int|null, name: string|null, slug: string|null, option: array{id: int, label: string}}>,
     *     thumbnail: array{url: string, thumbnail_url: string, alt: string|null}|null
     * }
     */
    public function toArray(Request $request): array
    {
        $variant = $this->relationLoaded('variant') ? $this->variant : null;
        $product = $variant?->relationLoaded('product') ? $variant->product : null;

        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'qty' => $this->qty,
            'unit_price' => $this->unit_price,
            'line_total' => number_format((float) $this->unit_price * $this->qty, 2, '.', ''),
            'product' => $product === null ? null : [
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'sku' => $variant?->sku,
            'attributes' => $this->publicAttributes($variant),
            'thumbnail' => $this->thumbnail($variant, $product),
        ];
    }

    /**
     * @return list<array{id: int|null, name: string|null, slug: string|null, option: array{id: int, label: string}}>
     */
    private function publicAttributes(?ProductVariant $variant): array
    {
        if ($variant === null || ! $variant->relationLoaded('attributeOptions')) {
            return [];
        }

        return $variant->attributeOptions
            ->map(fn ($option) => [
                'id' => $option->attribute?->id,
                'name' => $option->attribute?->name,
                'slug' => $option->attribute?->slug,
                'option' => [
                    'id' => $option->id,
                    'label' => $option->label,
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{url: string, thumbnail_url: string, alt: string|null}|null
     */
    private function thumbnail(?ProductVariant $variant, mixed $product): ?array
    {
        $image = $this->variantImage($variant) ?? $this->productPrimaryImage($product);
        if ($image === null || $image->path === null || $image->path === '') {
            return null;
        }

        return [
            'url' => CatalogImagePath::url($image->path),
            'thumbnail_url' => CatalogImagePath::url(CatalogImagePath::thumbnailPath($image->path)),
            'alt' => $image->alt,
        ];
    }

    private function variantImage(?ProductVariant $variant): ?ProductVariantImage
    {
        if ($variant === null || ! $variant->relationLoaded('images') || $variant->images->isEmpty()) {
            return null;
        }

        return $variant->images->firstWhere('is_primary', true) ?? $variant->images->sortBy('position')->first();
    }

    private function productPrimaryImage(mixed $product): ?ProductImage
    {
        if ($product === null || ! $product->relationLoaded('images')) {
            return null;
        }

        return $product->images->firstWhere('is_primary', true);
    }
}
