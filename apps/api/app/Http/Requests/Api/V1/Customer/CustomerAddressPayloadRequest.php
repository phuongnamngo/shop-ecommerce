<?php

namespace App\Http\Requests\Api\V1\Customer;

use App\Models\GeoWard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class CustomerAddressPayloadRequest extends FormRequest
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
            'label' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:32'],
            'province_code' => ['required', 'string', 'max:32'],
            'district_code' => ['required', 'string', 'max:32'],
            'ward_code' => ['required', 'string', 'max:32'],
            'address_line' => ['required', 'string', 'max:500'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $valid = GeoWard::query()
                ->where('code', $this->string('ward_code')->toString())
                ->whereHas('district', fn ($district) => $district->where('code', $this->string('district_code')->toString())
                    ->whereHas('province', fn ($province) => $province->where('code', $this->string('province_code')->toString())))
                ->exists();
            if (! $valid) {
                $validator->errors()->add('ward_code', 'The geo codes are invalid.');
            }
        }];
    }
}
