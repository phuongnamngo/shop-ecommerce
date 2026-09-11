<?php

namespace Database\Factories;

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlashSaleItem>
 */
class FlashSaleItemFactory extends Factory
{
    protected $model = FlashSaleItem::class;

    public function definition(): array
    {
        return [
            'flash_sale_id' => FlashSale::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'sale_price' => 50000,
            'qty_cap' => null,
            'qty_sold' => 0,
        ];
    }
}
