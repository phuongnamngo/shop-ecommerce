<?php

namespace App\Http\Requests\Api\V1\Checkout;

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
            'shipping_rate_id' => ['required', 'integer', 'exists:shipping_rates,id'],
            'coupon_code' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $hasSnapshot = is_array($this->input('shipping_address'));
            $hasCustomerAddress = $this->filled('customer_address_id');
            if ($hasSnapshot === $hasCustomerAddress) {
                $validator->errors()->add('shipping_address', 'Provide exactly one shipping address source.');
            }
        }];
    }
}
