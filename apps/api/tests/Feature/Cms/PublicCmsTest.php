<?php

use App\Models\CmsBanner;
use App\Models\CmsPage;
use App\Support\ErrorCode;

it('returns published page html and hides drafts', function () {
    CmsPage::factory()->published()->create([
        'slug' => 'about',
        'title' => 'Về chúng tôi',
        'body' => "# Hi\n\nWatch.",
    ]);
    CmsPage::factory()->create([
        'slug' => 'draft-only',
        'title' => 'Draft',
        'status' => CmsPage::STATUS_DRAFT,
    ]);

    $show = $this->getJson('/api/v1/cms/pages/about')->assertOk();
    expect($show->json('data.slug'))->toBe('about')
        ->and($show->json('data.title'))->toBe('Về chúng tôi')
        ->and($show->json('data'))->not->toHaveKey('body')
        ->and($show->json('data.body_html'))->toContain('<h1>')
        ->and($show->json('data.body_html'))->not->toContain('# Hi');

    $this->getJson('/api/v1/cms/pages/draft-only')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CMS_PAGE_NOT_FOUND]);

    $this->getJson('/api/v1/cms/pages/missing')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CMS_PAGE_NOT_FOUND]);

    $list = $this->getJson('/api/v1/cms/pages')->assertOk();
    $slugs = collect($list->json('data'))->pluck('slug')->all();
    expect($slugs)->toBe(['about'])
        ->and($list->json('data.0'))->not->toHaveKey('body_html')
        ->and($list->json('data.0'))->not->toHaveKey('body');
});

it('returns 404 for a soft-deleted published page', function () {
    $page = CmsPage::factory()->published()->create(['slug' => 'gone']);
    $page->delete();

    $this->getJson('/api/v1/cms/pages/gone')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CMS_PAGE_NOT_FOUND]);
});

it('lists published pages in id ascending order', function () {
    CmsPage::factory()->published()->create(['slug' => 'zeta', 'title' => 'Z']);
    CmsPage::factory()->published()->create(['slug' => 'alpha', 'title' => 'A']);

    $this->getJson('/api/v1/cms/pages')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'zeta')
        ->assertJsonPath('data.1.slug', 'alpha');
});

it('groups active in-window banners and omits inactive or expired', function () {
    CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
        'title' => 'Promo later',
        'sort' => 0,
        'status' => CmsBanner::STATUS_ACTIVE,
        'starts_at' => now()->addDay(),
    ]);
    CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
        'title' => 'Promo inactive',
        'sort' => 0,
        'status' => CmsBanner::STATUS_INACTIVE,
    ]);
    CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
        'title' => 'Promo second',
        'sort' => 2,
        'status' => CmsBanner::STATUS_ACTIVE,
    ]);
    CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_PROMO_BAR,
        'title' => 'Promo first',
        'sort' => 1,
        'status' => CmsBanner::STATUS_ACTIVE,
    ]);
    CmsBanner::factory()->create([
        'placement' => CmsBanner::PLACEMENT_HOMEPAGE_HERO,
        'title' => 'Hero expired',
        'image_url' => '/storage/hero.jpg',
        'status' => CmsBanner::STATUS_ACTIVE,
        'ends_at' => now()->subDay(),
    ]);

    $this->getJson('/api/v1/cms/banners')
        ->assertOk()
        ->assertJsonPath('data.promo_bar.0.title', 'Promo first')
        ->assertJsonPath('data.promo_bar.1.title', 'Promo second')
        ->assertJsonPath('data.homepage_hero', [])
        ->assertJsonStructure(['data' => ['promo_bar' => [['id', 'placement', 'title', 'image_url', 'link_url', 'sort']], 'homepage_hero']]);
});

it('returns empty banner groups when none are live', function () {
    $this->getJson('/api/v1/cms/banners')
        ->assertOk()
        ->assertJsonPath('data.promo_bar', [])
        ->assertJsonPath('data.homepage_hero', []);
});

it('seeds published legal pages and promo plus hero banners', function () {
    $this->seed(\Database\Seeders\PlatformRemainderDemoSeeder::class);

    $list = $this->getJson('/api/v1/cms/pages')->assertOk();
    expect(collect($list->json('data'))->pluck('slug')->all())
        ->toEqualCanonicalizing(['about', 'shipping', 'returns', 'privacy']);

    $this->getJson('/api/v1/cms/pages/about')->assertOk();

    $banners = $this->getJson('/api/v1/cms/banners')->assertOk();
    expect($banners->json('data.promo_bar'))->not->toBeEmpty()
        ->and($banners->json('data.homepage_hero'))->not->toBeEmpty()
        ->and($banners->json('data.promo_bar.0.link_url'))->toBe('/products')
        ->and($banners->json('data.homepage_hero.0.link_url'))->toBe('/products');
});
