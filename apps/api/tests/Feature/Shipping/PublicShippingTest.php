<?php

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Support\ErrorCode;

it('lists active shipping methods with region-null rates without auth', function () {
    $active = ShippingMethod::query()->create(['code' => 'standard-pub', 'name' => 'Standard', 'status' => 'active']);
    $inactive = ShippingMethod::query()->create(['code' => 'hidden-pub', 'name' => 'Hidden', 'status' => 'inactive']);
    ShippingRate::query()->create(['shipping_method_id' => $active->id, 'region_code' => null, 'price' => 30000]);
    ShippingRate::query()->create(['shipping_method_id' => $active->id, 'region_code' => 'HN', 'price' => 50000]);
    ShippingRate::query()->create(['shipping_method_id' => $inactive->id, 'region_code' => null, 'price' => 10000]);

    $response = $this->getJson('/api/v1/shipping/methods')->assertOk();
    $codes = collect($response->json('data'))->pluck('code');
    expect($codes)->toContain('standard-pub')->not->toContain('hidden-pub');

    $standard = collect($response->json('data'))->firstWhere('code', 'standard-pub');
    expect($standard['rates'])->toHaveCount(1)
        ->and($standard['rates'][0]['price'])->toBe('30000.00')
        ->and($standard['rates'][0])->toHaveKeys(['id', 'price', 'min_order_amount', 'max_order_amount']);
});
