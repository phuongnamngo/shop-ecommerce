<?php

namespace App\Services\Promotion;

use App\Models\Discount;
use App\Models\DiscountRule;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PromotionDiscountService
{
    /**
     * @param  array{
     *     name: string,
     *     type: string,
     *     value: float|int|string,
     *     code?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     status?: string|null,
     *     rule?: array{conditions?: array<string, mixed>}|null
     * }  $data
     */
    public function create(array $data): Discount
    {
        return DB::transaction(function () use ($data): Discount {
            $discount = Discount::query()->create([
                'code' => $data['code'] ?? (string) Str::ulid(),
                'name' => $data['name'],
                'type' => $data['type'],
                'value' => $data['value'],
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'status' => $data['status'] ?? Discount::STATUS_ACTIVE,
            ]);

            if (array_key_exists('rule', $data) && $data['rule'] !== null) {
                $this->upsertRule($discount, $data['rule']['conditions'] ?? []);
            }

            return $discount->load('rules');
        });
    }

    /**
     * @param  array{
     *     name?: string,
     *     type?: string,
     *     value?: float|int|string,
     *     code?: string|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     status?: string|null,
     *     rule?: array{conditions?: array<string, mixed>}|null
     * }  $data
     */
    public function update(Discount $discount, array $data): Discount
    {
        return DB::transaction(function () use ($discount, $data): Discount {
            $discount->fill([
                'name' => $data['name'] ?? $discount->name,
                'type' => $data['type'] ?? $discount->type,
                'value' => $data['value'] ?? $discount->value,
                'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $discount->starts_at,
                'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $discount->ends_at,
                'status' => $data['status'] ?? $discount->status,
            ]);

            if (array_key_exists('code', $data) && $data['code'] !== null && $data['code'] !== '') {
                $discount->code = $data['code'];
            }

            $discount->save();

            if (array_key_exists('rule', $data)) {
                if ($data['rule'] === null) {
                    $discount->rules()->delete();
                } else {
                    $this->upsertRule($discount, $data['rule']['conditions'] ?? []);
                }
            }

            return $discount->refresh()->load('rules');
        });
    }

    public function delete(Discount $discount): void
    {
        if ($discount->coupons()->exists()) {
            throw new CommerceException(
                ErrorCode::PROMOTION_DISCOUNT_IN_USE,
                'Discount still has coupons.',
                status: 422,
            );
        }

        DB::transaction(function () use ($discount): void {
            $discount->rules()->delete();
            $discount->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function upsertRule(Discount $discount, array $conditions): void
    {
        $rule = $discount->rules()->first();
        if ($rule === null) {
            DiscountRule::query()->create([
                'discount_id' => $discount->id,
                'conditions' => $conditions,
            ]);

            return;
        }

        $rule->update(['conditions' => $conditions]);
        $discount->rules()->where('id', '!=', $rule->id)->delete();
    }
}
