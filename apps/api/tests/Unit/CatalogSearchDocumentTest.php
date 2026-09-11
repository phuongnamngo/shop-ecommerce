<?php

use App\Support\CatalogSearchDocument;

it('maps price buckets without overlap at 300000 and 500000', function () {
    expect(CatalogSearchDocument::priceBucket(299999.99))->toBe('lt_300k')
        ->and(CatalogSearchDocument::priceBucket(300000))->toBe('300_500k')
        ->and(CatalogSearchDocument::priceBucket(499999.99))->toBe('300_500k')
        ->and(CatalogSearchDocument::priceBucket(500000))->toBe('500k_plus');
});
