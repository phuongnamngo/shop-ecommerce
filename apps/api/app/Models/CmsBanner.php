<?php

namespace App\Models;

use Database\Factories\CmsBannerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'placement', 'title', 'image_url', 'link_url', 'starts_at', 'ends_at', 'sort', 'status',
])]
class CmsBanner extends Model
{
    /** @use HasFactory<CmsBannerFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const PLACEMENT_PROMO_BAR = 'promo_bar';

    public const PLACEMENT_HOMEPAGE_HERO = 'homepage_hero';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
