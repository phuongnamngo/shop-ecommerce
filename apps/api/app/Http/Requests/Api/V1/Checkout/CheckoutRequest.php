<?php

namespace App\Http\Requests\Api\V1\Checkout;

use App\Models\ShippingMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id', 'required_without:shipping_address', 'prohibits:shipping_address'],
            'shipping_address' => ['nullable', 'array', 'required_without:customer_address_id', 'prohibits:customer_address_id'],
            'shipping_address.recipient_name' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.phone' => ['required_with:shipping_address', 'string', 'max:32'],
            'shipping_address.province_code' => ['required_with:shipping_address', 'string', 'max:32'],
            'shipping_address.district_code' => ['required_with:shipping_address', 'string', 'max:32'],
            'shipping_address.ward_code' => ['required_with:shipping_address', 'string', 'max:32'],
            'shipping_address.address_line' => ['required_with:shipping_address', 'string', 'max:500'],
            'shipping_method_id' => ['required', 'integer', 'exists:shipping_methods,id'],
            'shipping_rate_id' => ['nullable', 'integer', 'exists:shipping_rates,id'],
            'ghn_service_id' => ['nullable', 'integer'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'payment_method_code' => ['required', 'string', 'in:cod,vnpay'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasSnapshot = is_array($this->input('shipping_address'));
                $hasCustomerAddress = $this->filled('customer_address_id');
                if ($hasSnapshot === $hasCustomerAddress) {
                    $validator->errors()->add('shipping_address', 'Provide exactly one shipping address source.');
                }
            },
            function (Validator $validator): void {
                $methodId = $this->input('shipping_method_id');
                if (! is_numeric($methodId)) {
                    return;
                }
                $code = ShippingMethod::query()->whereKey($methodId)->value('code');
                if ($code === 'ghn') {
                    if ($this->filled('shipping_rate_id')) {
                        $validator->errors()->add('shipping_rate_id', 'Do not send a shipping rate for GHN checkout.');
                    }
                    if (! $this->filled('ghn_service_id')) {
                        $validator->errors()->add('ghn_service_id', 'A GHN service is required.');
                    }
                } else {
                    if (! $this->filled('shipping_rate_id')) {
                        $validator->errors()->add('shipping_rate_id', 'A shipping rate is required.');
                    }
                    if ($this->filled('ghn_service_id')) {
                        $validator->errors()->add('ghn_service_id', 'Do not send a GHN service for this shipping method.');
                    }
                }
            },
        ];
    }
}
