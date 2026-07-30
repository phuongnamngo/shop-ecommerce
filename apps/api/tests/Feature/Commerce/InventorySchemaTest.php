<?php

use App\Models\ProductVariant;
use App\Models\StockItem;
use App\Models\Warehouse;
use Illuminate\Database\QueryException;

it('enforces unique stock item per warehouse and variant', function () {
    $warehouse = Warehouse::factory()->create();
    $variant = ProductVariant::factory()->create();

    StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 10,
        'qty_reserved' => 0,
    ]);

    expect(fn () => StockItem::query()->create([
        'warehouse_id' => $warehouse->id,
        'product_variant_id' => $variant->id,
        'qty_on_hand' => 1,
        'qty_reserved' => 0,
    ]))->toThrow(QueryException::class);
});
