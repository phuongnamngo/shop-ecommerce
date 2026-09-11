<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWishlistItemRequest extends FormRequest
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
            'product_variant_id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('status', ProductVariant::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],
        ];
    }
}
