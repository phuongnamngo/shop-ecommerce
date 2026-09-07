<?php

namespace App\Http\Requests\Api\V1\Admin\Catalog;

use App\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductVariantRequest extends FormRequest
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
            'sku' => ['required', 'string', 'max:255'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_default' => ['required', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in([ProductVariant::STATUS_DRAFT, ProductVariant::STATUS_ACTIVE, ProductVariant::STATUS_INACTIVE])],
            'attribute_option_ids' => ['sometimes', 'array'],
            'attribute_option_ids.*' => ['integer', Rule::exists('attribute_options', 'id')->whereNull('deleted_at')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            CatalogAttributeOptionRules::assertUniqueAttributes($validator, [$this->all()]);
        });
    }
}
