<?php

namespace App\Http\Resources\Cms;

use App\Models\CmsPage;
use App\Support\CmsMarkdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CmsPage
 */
class PublicPageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'body_html' => CmsMarkdown::html((string) $this->body),
            'published_at' => $this->published_at,
        ];
    }
}
