<?php

use App\Support\CatalogSlug;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

it('slugifies name when request slug is blank', function () {
    expect(CatalogSlug::resolve('Áo Thun Demo', null))->toBe(Str::slug('Áo Thun Demo'))
        ->and(CatalogSlug::resolve('Keep', 'custom-slug'))->toBe('custom-slug')
        ->and(CatalogSlug::resolve('Keep', ''))->toBe(Str::slug('Keep'));
});
