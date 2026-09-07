<?php

namespace App\Http\Resources\Catalog;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class AdminProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'is_default' => $this->is_default,
            'status' => $this->status,
            'attributes' => $this->whenLoaded('attributeOptions', function () {
                return $this->attributeOptions->map(function ($option) {
                    return [
                        'id' => $option->attribute?->id,
                        'name' => $option->attribute?->name,
                        'slug' => $option->attribute?->slug,
                        'option' => [
                            'id' => $option->id,
                            'label' => $option->label,
                        ],
                    ];
                })->values()->all();
            }),
            'images' => CatalogImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
