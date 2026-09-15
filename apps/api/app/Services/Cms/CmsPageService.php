<?php

namespace App\Services\Cms;

use App\Models\CmsPage;
use App\Support\CmsSlug;

final class CmsPageService
{
    /**
     * @param  array{title: string, body?: string|null, slug?: string|null, status?: string|null}  $data
     */
    public function create(array $data): CmsPage
    {
        $slug = CmsSlug::resolve($data['title'], $data['slug'] ?? null);
        CmsSlug::assertUnique($slug);

        $status = $data['status'] ?? CmsPage::STATUS_DRAFT;
        $publishedAt = $status === CmsPage::STATUS_PUBLISHED ? now() : null;

        return CmsPage::query()->create([
            'slug' => $slug,
            'title' => $data['title'],
            'body' => $data['body'] ?? '',
            'status' => $status,
            'published_at' => $publishedAt,
        ]);
    }

    /**
     * @param  array{title?: string, body?: string|null, slug?: string|null, status?: string|null}  $data
     */
    public function update(CmsPage $page, array $data): CmsPage
    {
        $title = $data['title'] ?? $page->title;
        $slug = $page->slug;
        if (array_key_exists('slug', $data)) {
            $slug = CmsSlug::forUpdate($page->slug, $title, $data['slug'], $page->id);
        }

        $status = $data['status'] ?? $page->status;
        $publishedAt = $page->published_at;
        if ($status === CmsPage::STATUS_PUBLISHED && $publishedAt === null) {
            $publishedAt = now();
        }

        $page->fill([
            'title' => $title,
            'slug' => $slug,
            'body' => array_key_exists('body', $data) ? (string) $data['body'] : $page->body,
            'status' => $status,
            'published_at' => $publishedAt,
        ])->save();

        return $page->refresh();
    }

    public function delete(CmsPage $page): void
    {
        $page->delete();
    }
}
