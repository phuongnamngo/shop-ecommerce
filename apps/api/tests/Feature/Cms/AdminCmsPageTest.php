<?php

use App\Models\CmsPage;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows staff to list pages and forbids writes', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/cms/pages')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/cms/pages', ['title' => 'About', 'body' => 'Hi'])
        ->assertForbidden();
});

it('creates a page with auto slug and returns 201 envelope', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/pages', [
            'title' => 'Về chúng tôi',
            'body' => '# Hello',
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Về chúng tôi')
        ->assertJsonPath('data.slug', Str::slug('Về chúng tôi'))
        ->assertJsonPath('data.body', '# Hello')
        ->assertJsonPath('data.status', CmsPage::STATUS_DRAFT)
        ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'body', 'status', 'published_at']]);
});

it('rejects a live duplicate slug', function () {
    CmsPage::factory()->create(['slug' => 'about']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/pages', ['title' => 'Other', 'slug' => 'about', 'body' => 'x'])
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::CMS_SLUG_TAKEN, 'field' => 'slug']);
});

it('returns CMS_PAGE_NOT_FOUND for a missing page', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/cms/pages/999999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CMS_PAGE_NOT_FOUND]);
});

it('filters admin pages by status', function () {
    CmsPage::factory()->published()->create(['title' => 'Live']);
    CmsPage::factory()->create(['title' => 'Draft']);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/cms/pages?status=published')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.status', CmsPage::STATUS_PUBLISHED);
});

it('sets published_at when moving draft to published and keeps it on later patches', function () {
    $create = $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/cms/pages', [
            'title' => 'Shipping',
            'body' => 'Ship',
            'status' => CmsPage::STATUS_DRAFT,
        ])
        ->assertCreated();

    $id = $create->json('data.id');
    expect($create->json('data.published_at'))->toBeNull();

    $published = $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/cms/pages/'.$id, ['status' => CmsPage::STATUS_PUBLISHED])
        ->assertOk();

    $publishedAt = $published->json('data.published_at');
    expect($publishedAt)->not->toBeNull();

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/cms/pages/'.$id, ['title' => 'Shipping updated'])
        ->assertOk()
        ->assertJsonPath('data.published_at', $publishedAt)
        ->assertJsonPath('data.title', 'Shipping updated');
});

it('soft-deletes a page with 200 then hides it from public', function () {
    $page = CmsPage::factory()->published()->create(['slug' => 'returns', 'body' => 'R']);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/cms/pages/'.$page->id)
        ->assertOk()
        ->assertJsonPath('data', null);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->patchJson('/api/v1/admin/cms/pages/'.$page->id, ['title' => 'Nope'])
        ->assertForbidden();

    $this->getJson('/api/v1/cms/pages/returns')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::CMS_PAGE_NOT_FOUND]);
});

it('forbids staff from patching or deleting a page', function () {
    $page = CmsPage::factory()->create();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->patchJson('/api/v1/admin/cms/pages/'.$page->id, ['title' => 'Nope'])
        ->assertForbidden();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->deleteJson('/api/v1/admin/cms/pages/'.$page->id)
        ->assertForbidden();
});
