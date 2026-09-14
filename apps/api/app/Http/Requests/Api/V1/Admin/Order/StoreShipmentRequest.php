<?php

namespace App\Http\Requests\Api\V1\Admin\Order;

use App\Models\Order;
use App\Support\CommerceException;
use App\Support\ErrorCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'carrier_code' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $order = Order::query()->with('shippingMethod')->find($this->route('id'));
            if ($order?->shippingMethod?->code === 'standard' && trim((string) $this->input('tracking_number')) === '') {
                throw new CommerceException(ErrorCode::SHIPMENT_TRACKING_REQUIRED, 'Tracking number is required.', 'tracking_number');
            }
        });
    }
}
