<?php

namespace App\Http\Resources\Identity;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
final class AdminCustomerResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     email: string,
     *     phone: string|null,
     *     status: string,
     *     created_at: string|null,
     *     last_login_at: string|null,
     *     email_verified_at: string|null,
     *     addresses?: list<array{
     *         id: int,
     *         label: string|null,
     *         recipient_name: string,
     *         phone: string,
     *         province_code: string,
     *         district_code: string,
     *         ward_code: string,
     *         address_line: string,
     *         postal_code: string|null,
     *         is_default: bool
     *     }>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => (string) $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'addresses' => $this->whenLoaded('addresses', fn () => $this->addresses->map(
                fn (CustomerAddress $address) => [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'province_code' => $address->province_code,
                    'district_code' => $address->district_code,
                    'ward_code' => $address->ward_code,
                    'address_line' => $address->address_line,
                    'postal_code' => $address->postal_code,
                    'is_default' => (bool) $address->is_default,
                ],
            )->all()),
        ];
    }
}
