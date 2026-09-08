<?php

namespace App\Http\Requests\Api\V1\Admin\Order;

use Illuminate\Foundation\Http\FormRequest;

final class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:255'],
            'carrier_code' => ['nullable', 'string', 'max:64'],
        ];
    }
}
