<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['product_variant_id' => ['required', 'integer', 'exists:product_variants,id'], 'qty' => ['required', 'integer', 'min:1']];
    }
}
