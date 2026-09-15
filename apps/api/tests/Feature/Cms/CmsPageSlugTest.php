<?php

use App\Models\CmsPage;
use App\Support\CmsException;
use App\Support\CmsSlug;
use App\Support\ErrorCode;

it('rejects a live duplicate slug and allows reuse after soft delete', function () {
    CmsPage::factory()->create(['slug' => 'about']);

    try {
        CmsSlug::assertUnique('about');
        expect(false)->toBeTrue();
    } catch (CmsException $e) {
        expect($e->errorCode)->toBe(ErrorCode::CMS_SLUG_TAKEN)
            ->and($e->field)->toBe('slug')
            ->and($e->status)->toBe(422);
    }

    CmsPage::query()->where('slug', 'about')->first()?->delete();
    CmsSlug::assertUnique('about');
    CmsPage::factory()->create(['slug' => 'about']);
    expect(CmsPage::withTrashed()->where('slug', 'about')->count())->toBe(2);
});
