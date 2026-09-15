<?php

use App\Models\CmsBanner;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('creates a promo banner without image', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/banners', [
            'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
            'title' => 'Free shipping',
            'link_url' => '/products',
        ])
        ->assertCreated()
        ->assertJsonPath('data.placement', CmsBanner::PLACEMENT_PROMO_BAR)
        ->assertJsonPath('data.image_url', null)
        ->assertJsonPath('data.link_url', '/products');
});

it('accepts https link urls on banners', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/banners', [
            'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
            'title' => 'External',
            'link_url' => 'https://example.com/sale',
        ])
        ->assertCreated()
        ->assertJsonPath('data.link_url', 'https://example.com/sale');
});

it('requires an image when creating a homepage hero', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/banners', [
            'placement' => CmsBanner::PLACEMENT_HOMEPAGE_HERO,
            'title' => 'Hero',
            'link_url' => '/products',
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['field' => 'image_url']);
});

it('rejects patching a hero to a null image when the result would be empty', function () {
    $banner = CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_HOMEPAGE_HERO,
        'image_url' => '/storage/hero.jpg',
        'title' => 'Hero',
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/cms/banners/'.$banner->id, [
            'image_url' => null,
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['field' => 'image_url']);
});

it('allows patching a hero title without resending image_url', function () {
    $banner = CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_HOMEPAGE_HERO,
        'image_url' => '/storage/hero.jpg',
        'title' => 'Hero',
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/cms/banners/'.$banner->id, [
            'title' => 'Hero updated',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Hero updated')
        ->assertJsonPath('data.image_url', '/storage/hero.jpg');
});

it('rejects a placement outside the allowlist', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/banners', [
            'placement' => 'carousel',
            'title' => 'Nope',
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['field' => 'placement']);
});

it('rejects ends_at before starts_at', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/banners', [
            'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
            'title' => 'Window',
            'starts_at' => '2026-09-15T12:00:00+00:00',
            'ends_at' => '2026-09-14T12:00:00+00:00',
        ])
        ->assertUnprocessable()
        ->assertJsonFragment(['field' => 'ends_at']);
});

it('returns CMS_BANNER_NOT_FOUND for a missing banner', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/cms/banners/999999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CMS_BANNER_NOT_FOUND]);
});

it('filters admin banners by placement', function () {
    CmsBanner::factory()->create(['placement' => CmsBanner::PLACEMENT_PROMO_BAR, 'title' => 'Promo']);
    CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_HOMEPAGE_HERO,
        'title' => 'Hero',
        'image_url' => '/storage/hero.jpg',
    ]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/cms/banners?placement=homepage_hero')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.placement', CmsBanner::PLACEMENT_HOMEPAGE_HERO);
});

it('forbids staff from writing banners', function () {
    $banner = CmsBanner::factory()->create();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/cms/banners')
        ->assertOk();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/cms/banners', [
            'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
            'title' => 'Nope',
        ])
        ->assertForbidden();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->patchJson('/api/v1/admin/cms/banners/'.$banner->id, ['title' => 'Nope'])
        ->assertForbidden();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->deleteJson('/api/v1/admin/cms/banners/'.$banner->id)
        ->assertForbidden();
});

it('soft-deletes a banner with 200', function () {
    $banner = CmsBanner::factory()->create();

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/cms/banners/'.$banner->id)
        ->assertOk()
        ->assertJsonPath('data', null);
});
