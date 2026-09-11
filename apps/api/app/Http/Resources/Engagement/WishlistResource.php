<?php

namespace App\Http\Resources\Engagement;

use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wishlist */
final class WishlistResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'items' => WishlistItemResource::collection($this->whenLoaded('items'))->resolve(),
        ];
    }
}
