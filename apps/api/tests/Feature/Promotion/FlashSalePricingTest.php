<?php

use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Services\Promotion\FlashSalePricingService;
use App\Services\Promotion\PromotionFlashSaleService;
use App\Support\CommerceException;
use App\Support\ErrorCode;

function flashSaleVariant(int $listPrice = 100000)
{
    $product = Product::factory()->published()->create();
    $variant = $product->variants()->firstOrFail();
    $variant->update(['status' => 'active', 'price' => $listPrice]);

    return $variant->refresh();
}

it('resolves sale price only when status is active and now is inside the window', function () {
    $variant = flashSaleVariant(100000);
    $sale = FlashSale::factory()->create([
        'status' => FlashSale::STATUS_ACTIVE,
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
    ]);
    FlashSaleItem::factory()->create([
        'flash_sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 70000,
        'qty_cap' => 8,
        'qty_sold' => 3,
    ]);

    $pricing = app(FlashSalePricingService::class);
    $offer = $pricing->offerForVariant($variant->id);

    expect($pricing->unitPrice($variant))->toBe('70000.00')
        ->and($offer)->not->toBeNull()
        ->and($offer->qtyRemaining())->toBe(5);
});

it('ignores scheduled ended cancelled and out-of-window sales', function () {
    $variant = flashSaleVariant(100000);
    $pricing = app(FlashSalePricingService::class);

    foreach ([
        ['status' => FlashSale::STATUS_SCHEDULED, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()],
        ['status' => FlashSale::STATUS_ENDED, 'starts_at' => now()->subHours(3), 'ends_at' => now()->subHour()],
        ['status' => FlashSale::STATUS_CANCELLED, 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour()],
        ['status' => FlashSale::STATUS_ACTIVE, 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)],
    ] as $state) {
        $sale = FlashSale::factory()->create($state);
        FlashSaleItem::factory()->create([
            'flash_sale_id' => $sale->id,
            'product_variant_id' => $variant->id,
            'sale_price' => 40000,
        ]);
    }

    expect($pricing->unitPrice($variant))->toBe('100000.00')
        ->and($pricing->offerForVariant($variant->id))->toBeNull();
});

it('returns null qty remaining when qty_cap is unrestricted', function () {
    $variant = flashSaleVariant();
    $sale = FlashSale::factory()->create();
    $item = FlashSaleItem::factory()->create([
        'flash_sale_id' => $sale->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 80000,
        'qty_cap' => null,
        'qty_sold' => 12,
    ]);

    expect($item->qtyRemaining())->toBeNull();
});

it('picks the lowest sale price then the earliest starts_at when two effective offers collide', function () {
    $variant = flashSaleVariant(200000);
    $later = FlashSale::factory()->create([
        'starts_at' => now()->subMinutes(10),
        'ends_at' => now()->addHour(),
        'status' => FlashSale::STATUS_ACTIVE,
    ]);
    $earlier = FlashSale::factory()->create([
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'status' => FlashSale::STATUS_ACTIVE,
    ]);
    FlashSaleItem::factory()->create([
        'flash_sale_id' => $later->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 90000,
    ]);
    FlashSaleItem::factory()->create([
        'flash_sale_id' => $earlier->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 90000,
    ]);

    $offer = app(FlashSalePricingService::class)->offerForVariant($variant->id);

    expect($offer?->flash_sale_id)->toBe($earlier->id);
});

it('rejects overlapping scheduled or active windows for the same variant', function () {
    $variant = flashSaleVariant(100000);
    $existing = FlashSale::factory()->create([
        'status' => FlashSale::STATUS_SCHEDULED,
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(5),
    ]);
    FlashSaleItem::factory()->create([
        'flash_sale_id' => $existing->id,
        'product_variant_id' => $variant->id,
        'sale_price' => 60000,
    ]);

    try {
        app(PromotionFlashSaleService::class)->create([
            'name' => 'Clash',
            'starts_at' => now()->addHours(4),
            'ends_at' => now()->addHours(8),
            'status' => FlashSale::STATUS_ACTIVE,
            'items' => [
                ['product_variant_id' => $variant->id, 'sale_price' => 55000],
            ],
        ]);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::FLASH_SALE_OVERLAP)
            ->and($e->status)->toBe(422);
    }
});

it('rejects sale_price that is not strictly below list price', function () {
    $variant = flashSaleVariant(50000);

    try {
        app(PromotionFlashSaleService::class)->create([
            'name' => 'Too high',
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'status' => FlashSale::STATUS_ACTIVE,
            'items' => [
                ['product_variant_id' => $variant->id, 'sale_price' => 50000],
            ],
        ]);
        expect(false)->toBeTrue();
    } catch (CommerceException $e) {
        expect($e->errorCode)->toBe(ErrorCode::FLASH_SALE_INVALID);
    }
});

it('preserves qty_sold when replacing items on update', function () {
    $variant = flashSaleVariant(100000);
    $sale = app(PromotionFlashSaleService::class)->create([
        'name' => 'Keep sold',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'status' => FlashSale::STATUS_ACTIVE,
        'items' => [
            ['product_variant_id' => $variant->id, 'sale_price' => 70000, 'qty_cap' => 10],
        ],
    ]);
    $sale->items()->firstOrFail()->update(['qty_sold' => 4]);

    $updated = app(PromotionFlashSaleService::class)->update($sale, [
        'items' => [
            ['product_variant_id' => $variant->id, 'sale_price' => 65000, 'qty_cap' => 10],
        ],
    ]);

    expect((int) $updated->items->first()->qty_sold)->toBe(4)
        ->and($updated->items->first()->sale_price)->toBe('65000.00');
});
