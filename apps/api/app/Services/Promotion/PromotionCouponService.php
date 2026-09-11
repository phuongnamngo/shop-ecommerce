<?php

namespace App\Services\Promotion;

use App\Models\Coupon;
use App\Models\Discount;
use App\Support\CommerceException;
use App\Support\ErrorCode;

final class PromotionCouponService
{
    /**
     * @param  array{
     *     code: string,
     *     discount_id: int,
     *     max_uses?: int|null,
     *     max_uses_per_customer?: int|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     status?: string|null
     * }  $data
     */
    public function create(array $data): Coupon
    {
        $this->assertDiscountExists((int) $data['discount_id']);

        return Coupon::query()->create([
            'code' => $data['code'],
            'discount_id' => $data['discount_id'],
            'max_uses' => $data['max_uses'] ?? null,
            'max_uses_per_customer' => $data['max_uses_per_customer'] ?? null,
            'used_count' => 0,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $data['status'] ?? Coupon::STATUS_ACTIVE,
        ])->load('discount');
    }

    /**
     * @param  array{
     *     code?: string,
     *     discount_id?: int,
     *     max_uses?: int|null,
     *     max_uses_per_customer?: int|null,
     *     starts_at?: string|null,
     *     ends_at?: string|null,
     *     status?: string|null
     * }  $data
     */
    public function update(Coupon $coupon, array $data): Coupon
    {
        if (array_key_exists('discount_id', $data)) {
            $this->assertDiscountExists((int) $data['discount_id']);
        }

        $coupon->fill([
            'code' => $data['code'] ?? $coupon->code,
            'discount_id' => $data['discount_id'] ?? $coupon->discount_id,
            'max_uses' => array_key_exists('max_uses', $data) ? $data['max_uses'] : $coupon->max_uses,
            'max_uses_per_customer' => array_key_exists('max_uses_per_customer', $data)
                ? $data['max_uses_per_customer']
                : $coupon->max_uses_per_customer,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $coupon->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $coupon->ends_at,
            'status' => $data['status'] ?? $coupon->status,
        ])->save();

        return $coupon->refresh()->load('discount');
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    private function assertDiscountExists(int $discountId): void
    {
        if (! Discount::query()->whereKey($discountId)->exists()) {
            throw new CommerceException(
                ErrorCode::PROMOTION_NOT_FOUND,
                'Discount not found.',
                'discount_id',
                422,
            );
        }
    }
}
