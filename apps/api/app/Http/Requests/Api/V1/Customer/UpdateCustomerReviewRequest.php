<?php

namespace App\Http\Requests\Api\V1\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerReviewRequest extends FormRequest
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
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('product_variants', 'id')->whereNull('deleted_at'),
            ],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'body' => ['sometimes', 'nullable', 'string'],
            'images' => ['sometimes', 'array', 'max:3'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_image_ids' => ['sometimes', 'array'],
            'remove_image_ids.*' => ['integer'],
        ];
    }
}
