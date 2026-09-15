<?php

namespace App\Http\Resources\Cms;

use App\Models\CmsBanner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CmsBanner
 */
class PublicBannerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'placement' => $this->placement,
            'title' => $this->title,
            'image_url' => $this->image_url,
            'link_url' => $this->link_url,
            'sort' => $this->sort,
        ];
    }
}
