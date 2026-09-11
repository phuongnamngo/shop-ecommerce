<?php

namespace App\Http\Resources\Engagement;

use App\Models\ProductReview;
use App\Support\CatalogImagePath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductReview */
final class CustomerReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'rating' => $this->rating,
            'body' => $this->body,
            'status' => $this->status,
            'images' => $this->whenLoaded('images', fn () => $this->images
                ->sortBy('sort')
                ->values()
                ->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => CatalogImagePath::url($image->path),
                    'thumbnail_url' => CatalogImagePath::url(CatalogImagePath::thumbnailPath($image->path)),
                ])
                ->all()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
