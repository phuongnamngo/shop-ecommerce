<?php

namespace App\Http\Requests\Api\V1\Admin\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['warehouse_id' => ['required', 'integer', 'exists:warehouses,id'], 'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'], 'type' => ['required', 'in:receipt,issue,adjustment'], 'qty' => ['required', 'integer', 'not_in:0'], 'note' => ['nullable', 'string']];
    }
}
