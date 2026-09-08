<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CommerceFulfillmentDemoSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::query()->firstOrCreate(
            ['name' => 'Default Warehouse'],
            [
                'code' => (string) Str::ulid(),
                'is_default' => true,
                'status' => 'active',
            ],
        );

        foreach ([
            ['cod', 'COD', true],
            ['vnpay', 'VNPay', true],
            ['momo', 'MoMo', false],
        ] as [$code, $name, $active]) {
            PaymentMethod::query()->updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'is_active' => $active],
            );
        }
        PaymentMethod::query()->where('code', 'momo')->update(['is_active' => false]);

        $method = ShippingMethod::query()->firstOrCreate(
            ['code' => 'standard'],
            [
                'name' => 'Standard',
                'provider' => null,
                'status' => 'active',
            ],
        );

        ShippingRate::query()->firstOrCreate(
            [
                'shipping_method_id' => $method->id,
                'region_code' => null,
                'price' => 30000,
            ],
            [
                'min_order_amount' => null,
                'max_order_amount' => null,
            ],
        );
    }
}
