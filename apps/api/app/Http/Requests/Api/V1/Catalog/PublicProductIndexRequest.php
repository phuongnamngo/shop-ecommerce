<?php

namespace App\Http\Requests\Api\V1\Catalog;

use App\Support\CatalogSearchDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicProductIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $facets = $this->input('attribute_facets');
        if (is_string($facets) && $facets !== '') {
            $this->merge(['attribute_facets' => [$facets]]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string'],
            'brand_id' => ['sometimes', 'nullable', 'integer'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'sort' => ['sometimes', 'nullable', 'string', Rule::in(['newest', 'price_asc', 'price_desc'])],
            'price_bucket' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(CatalogSearchDocument::PRICE_BUCKET_LABELS))],
            'attribute_facets' => ['sometimes', 'array'],
            'attribute_facets.*' => ['string', 'regex:/^[a-z0-9-]+:[a-z0-9-]+(?:-\d+)?$/i'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
