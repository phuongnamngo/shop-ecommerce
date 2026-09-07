<?php

namespace App\Http\Resources\Catalog;

use App\Models\ProductImage;
use App\Support\CatalogImagePath;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductImage
 */
class CatalogImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'path' => $this->path,
            'alt' => $this->alt,
            'position' => $this->position,
            'is_primary' => $this->is_primary,
            'url' => CatalogImagePath::url($this->path),
            'thumbnail_url' => CatalogImagePath::url(CatalogImagePath::thumbnailPath($this->path)),
        ];
    }
}
