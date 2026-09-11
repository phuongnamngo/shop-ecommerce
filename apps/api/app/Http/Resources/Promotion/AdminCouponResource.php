<?php

namespace App\Http\Resources\Promotion;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Coupon
 */
class AdminCouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $discount = $this->relationLoaded('discount') ? $this->discount : null;

        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_id' => $this->discount_id,
            'discount' => $discount === null ? null : [
                'id' => $discount->id,
                'code' => $discount->code,
                'name' => $discount->name,
                'type' => $discount->type,
                'value' => $discount->value,
                'status' => $discount->status,
            ],
            'max_uses' => $this->max_uses,
            'max_uses_per_customer' => $this->max_uses_per_customer,
            'used_count' => (int) $this->used_count,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
