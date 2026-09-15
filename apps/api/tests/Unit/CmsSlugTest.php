<?php

use App\Support\CmsSlug;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

it('slugifies title when request slug is blank', function () {
    expect(CmsSlug::resolve('Về chúng tôi', null))->toBe(Str::slug('Về chúng tôi'))
        ->and(CmsSlug::resolve('Keep', 'custom-slug'))->toBe('custom-slug')
        ->and(CmsSlug::resolve('Keep', ''))->toBe(Str::slug('Keep'));
});
