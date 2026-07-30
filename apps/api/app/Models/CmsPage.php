<?php

namespace App\Models;

use Database\Factories\CmsPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['slug', 'title', 'body', 'status', 'published_at'])]
class CmsPage extends Model
{
    /** @use HasFactory<CmsPageFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }
}
