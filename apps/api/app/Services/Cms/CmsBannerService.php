<?php

namespace App\Services\Cms;

use App\Models\CmsBanner;

final class CmsBannerService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CmsBanner
    {
        return CmsBanner::query()->create([
            'placement' => $data['placement'],
            'title' => $data['title'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'link_url' => $data['link_url'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? CmsBanner::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CmsBanner $banner, array $data): CmsBanner
    {
        $banner->fill([
            'placement' => $data['placement'] ?? $banner->placement,
            'title' => array_key_exists('title', $data) ? $data['title'] : $banner->title,
            'image_url' => array_key_exists('image_url', $data) ? $data['image_url'] : $banner->image_url,
            'link_url' => array_key_exists('link_url', $data) ? $data['link_url'] : $banner->link_url,
            'starts_at' => array_key_exists('starts_at', $data) ? $data['starts_at'] : $banner->starts_at,
            'ends_at' => array_key_exists('ends_at', $data) ? $data['ends_at'] : $banner->ends_at,
            'sort' => $data['sort'] ?? $banner->sort,
            'status' => $data['status'] ?? $banner->status,
        ])->save();

        return $banner->refresh();
    }

    public function delete(CmsBanner $banner): void
    {
        $banner->delete();
    }
}
