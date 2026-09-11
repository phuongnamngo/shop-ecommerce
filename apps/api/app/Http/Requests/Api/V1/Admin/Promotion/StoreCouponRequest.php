<?php

namespace App\Http\Requests\Api\V1\Admin\Promotion;

use App\Models\Coupon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'discount_id' => ['required', 'integer', Rule::exists('discounts', 'id')->whereNull('deleted_at')],
            'max_uses' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_uses_per_customer' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'string', Rule::in([Coupon::STATUS_ACTIVE, Coupon::STATUS_INACTIVE])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);
        if (is_array($data)) {
            unset($data['used_count']);
        }

        return $data;
    }
}
