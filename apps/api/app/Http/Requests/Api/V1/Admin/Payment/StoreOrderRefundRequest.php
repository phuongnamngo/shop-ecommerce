<?php

namespace App\Http\Requests\Api\V1\Admin\Payment;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRefundRequest extends FormRequest
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
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
