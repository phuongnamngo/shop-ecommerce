<?php

namespace App\Http\Resources\Catalog;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'default_variant' => $this->whenLoaded('defaultVariant', fn () => $this->defaultVariant ? [
                'sku' => $this->defaultVariant->sku,
                'price' => $this->defaultVariant->price,
            ] : null),
        ];
    }
}
