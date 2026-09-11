<?php

namespace App\Http\Resources\Promotion;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Discount
 */
class AdminDiscountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rule = $this->relationLoaded('rules')
            ? $this->rules->first()
            : $this->rules()->first();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => $this->status,
            'rule' => $rule === null ? null : [
                'id' => $rule->id,
                'conditions' => $rule->conditions ?? [],
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
