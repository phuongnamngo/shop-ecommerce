<?php

use Illuminate\Support\Facades\Schema;

it('adds seo columns on products and categories', function () {
    expect(Schema::hasColumns('products', ['description', 'meta_title', 'meta_description']))->toBeTrue()
        ->and(Schema::hasColumns('categories', ['description', 'meta_title', 'meta_description']))->toBeTrue();
});
