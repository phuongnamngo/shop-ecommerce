<?php

namespace App\Http\Resources\Engagement;

use App\Models\ProductReview;
use App\Support\CatalogImagePath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductReview */
final class AdminReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->relationLoaded('customer') ? $this->customer : null;
        $product = $this->relationLoaded('product') ? $this->product : null;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'rating' => $this->rating,
            'body' => $this->body,
            'product_variant_id' => $this->product_variant_id,
            'product' => $product === null ? null : [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],
            'customer' => $customer === null ? null : [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
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
