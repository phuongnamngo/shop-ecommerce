<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Customer\StoreCustomerAddressRequest;
use App\Http\Requests\Api\V1\Customer\UpdateCustomerAddressRequest;
use App\Http\Resources\Identity\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Support\ApiResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AddressController extends Controller
{
    #[Response(200, 'Customer addresses.', type: 'array{data: list<CustomerAddressResource>, meta: object}')]
    public function index(Request $request): JsonResponse
    {
        $rows = CustomerAddress::query()
            ->where('customer_id', $request->user('customer')->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(CustomerAddressResource::collection($rows)->resolve());
    }

    #[Response(201, 'Created customer address.', type: 'array{data: CustomerAddressResource, meta: object}')]
    public function store(StoreCustomerAddressRequest $request): JsonResponse
    {
        $address = $this->persist($request->user('customer')->id, $request->validated());

        return ApiResponse::success((new CustomerAddressResource($address))->resolve(), status: 201);
    }

    #[Response(200, 'Updated customer address.', type: 'array{data: CustomerAddressResource, meta: object}')]
    public function update(UpdateCustomerAddressRequest $request, int $id): JsonResponse
    {
        $address = $this->persist(
            $request->user('customer')->id,
            $request->validated(),
            $this->owned($request, $id),
        );

        return ApiResponse::success((new CustomerAddressResource($address))->resolve());
    }

    #[Response(200, 'Deleted customer address.', type: 'array{data: null, meta: object}')]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->owned($request, $id)->delete();

        return ApiResponse::success(null);
    }

    private function owned(Request $request, int $id): CustomerAddress
    {
        return CustomerAddress::query()
            ->where('customer_id', $request->user('customer')->id)
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function persist(int $customerId, array $data, ?CustomerAddress $existing = null): CustomerAddress
    {
        return DB::transaction(function () use ($customerId, $data, $existing) {
            $isDefault = array_key_exists('is_default', $data)
                ? (bool) $data['is_default']
                : (bool) ($existing?->is_default ?? false);

            if ($isDefault) {
                CustomerAddress::query()->where('customer_id', $customerId)->update(['is_default' => false]);
            }

            $payload = [
                'customer_id' => $customerId,
                'label' => $data['label'] ?? null,
                'recipient_name' => $data['recipient_name'],
                'phone' => $data['phone'],
                'province_code' => $data['province_code'],
                'district_code' => $data['district_code'],
                'ward_code' => $data['ward_code'],
                'address_line' => $data['address_line'],
                'postal_code' => $data['postal_code'] ?? null,
                'is_default' => $isDefault,
            ];

            if ($existing === null) {
                return CustomerAddress::query()->create($payload);
            }

            $existing->fill($payload)->save();

            return $existing->fresh();
        });
    }
}
