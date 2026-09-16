<?php

namespace App\Http\Requests\Api\V1\Admin\Activity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexActivityRequest extends FormRequest
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
            'log_name' => ['sometimes', 'nullable', 'string', Rule::in([
                'order',
                'product',
                'product_variant',
                'stock_movement',
            ])],
        ];
    }
}
