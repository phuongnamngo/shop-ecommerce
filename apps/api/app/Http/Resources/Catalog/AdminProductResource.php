<?php

namespace App\Http\Resources\Catalog;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class AdminProductResource extends JsonResource
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
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand === null ? null : [
                'id' => $this->brand->id,
                'code' => $this->brand->code,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'status' => $this->brand->status,
            ]),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'slug' => $category->slug,
                'status' => $category->status,
                'parent_id' => $category->parent_id,
            ])->values()->all()),
            'variants' => AdminProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => CatalogImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
