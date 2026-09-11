<?php

namespace App\Http\Requests\Api\V1\Admin\Promotion;

use App\Models\FlashSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFlashSaleRequest extends FormRequest
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
        $id = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:26', Rule::unique('flash_sales', 'code')->ignore($id)],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after_or_equal:starts_at'],
            'status' => ['sometimes', 'string', Rule::in([
                FlashSale::STATUS_SCHEDULED,
                FlashSale::STATUS_ACTIVE,
                FlashSale::STATUS_ENDED,
                FlashSale::STATUS_CANCELLED,
            ])],
            'items' => ['sometimes', 'array'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.sale_price' => ['required', 'numeric', 'gt:0'],
            'items.*.qty_cap' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
