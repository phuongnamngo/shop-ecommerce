<?php

namespace App\Http\Resources\Identity;

use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CustomerAddress */
final class CustomerAddressResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     label: string|null,
     *     recipient_name: string,
     *     phone: string,
     *     province_code: string,
     *     district_code: string,
     *     ward_code: string,
     *     address_line: string,
     *     postal_code: string|null,
     *     is_default: bool
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'province_code' => $this->province_code,
            'district_code' => $this->district_code,
            'ward_code' => $this->ward_code,
            'address_line' => $this->address_line,
            'postal_code' => $this->postal_code,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
