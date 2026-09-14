<?php

namespace App\Http\Requests\Api\V1\Shipping;

use Illuminate\Foundation\Http\FormRequest;

final class ShippingQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'province_code' => ['required', 'string', 'max:32'],
            'district_code' => ['required', 'string', 'max:32'],
            'ward_code' => ['required', 'string', 'max:32'],
        ];
    }
}
