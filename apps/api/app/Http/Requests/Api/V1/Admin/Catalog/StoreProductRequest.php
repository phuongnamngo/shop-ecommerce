<?php

namespace App\Http\Requests\Api\V1\Admin\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brand_id' => ['sometimes', 'nullable', 'integer', Rule::exists('brands', 'id')->whereNull('deleted_at')],
            'status' => ['sometimes', 'string', Rule::in([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, Product::STATUS_INACTIVE])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'nullable', 'string'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'variants' => ['present', 'array'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'variants.*.is_default' => ['required', 'boolean'],
            'variants.*.status' => ['sometimes', 'string', Rule::in([ProductVariant::STATUS_DRAFT, ProductVariant::STATUS_ACTIVE, ProductVariant::STATUS_INACTIVE])],
            'variants.*.attribute_option_ids' => ['sometimes', 'array'],
            'variants.*.attribute_option_ids.*' => ['integer', Rule::exists('attribute_options', 'id')->whereNull('deleted_at')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            CatalogAttributeOptionRules::assertUniqueAttributes(
                $validator,
                $this->input('variants', []),
            );
        });
    }
}
