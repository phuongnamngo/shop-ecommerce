<?php

namespace App\Http\Resources\Engagement;

use App\Models\ProductReview;
use App\Support\CatalogImagePath;
use App\Support\CustomerNameMask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductReview */
final class PublicProductReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => CustomerNameMask::public($this->customer?->name),
            'rating' => $this->rating,
            'body' => $this->body,
            'product_variant_id' => $this->product_variant_id,
            'variant_label' => $this->variantLabel(),
            'images' => $this->whenLoaded('images', fn () => $this->images
                ->sortBy('sort')
                ->values()
                ->map(fn ($image) => [
                    'url' => CatalogImagePath::url($image->path),
                    'thumbnail_url' => CatalogImagePath::url(CatalogImagePath::thumbnailPath($image->path)),
                ])
                ->all()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function variantLabel(): ?string
    {
        $variant = $this->relationLoaded('variant') ? $this->variant : null;
        if ($variant === null) {
            return null;
        }

        $labels = $variant->relationLoaded('attributeOptions')
            ? $variant->attributeOptions->pluck('label')->filter()->values()
            : collect();

        if ($labels->isNotEmpty()) {
            return $labels->implode(' / ');
        }

        return $variant->sku;
    }
}
