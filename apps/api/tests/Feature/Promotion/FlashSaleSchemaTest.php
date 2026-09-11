<?php

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

it('persists flash sale qty_sold and order item flash_sale_item_id', function () {
    expect(Schema::hasColumn('flash_sale_items', 'qty_sold'))->toBeTrue()
        ->and(Schema::hasColumn('order_items', 'flash_sale_item_id'))->toBeTrue();
});

it('creates flash sales and items via factories', function () {
    $variant = Product::factory()->create()->variants()->firstOrFail();
    $sale = FlashSale::factory()->create(['name' => 'Noon drop']);
    $item = FlashSaleItem::factory()->create([
        'flash_sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 49000,
        'qty_cap' => 5,
    ]);

    expect($item->qty_sold)->toBe(0)
        ->and($item->qtyRemaining())->toBe(5)
        ->and($sale->items()->count())->toBe(1)
        ->and((new OrderItem)->isFillable('flash_sale_item_id'))->toBeTrue();
});
