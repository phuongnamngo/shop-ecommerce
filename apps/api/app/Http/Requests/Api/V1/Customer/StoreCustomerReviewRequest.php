<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerReviewRequest extends FormRequest
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
        $productId = $this->integer('product_id');

        return [
            'product_id' => ['required', 'integer', Rule::exists(Product::class, 'id')],
            'product_variant_id' => [
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->where('product_id', $productId)->whereNull('deleted_at'),
            ],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['nullable', 'string'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
