<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryProduct extends Pivot
{
    protected $table = 'category_product';

    public $incrementing = true;

    protected static function booted(): void
    {
        static::created(function (self $pivot): void {
            Product::query()->find($pivot->product_id)?->syncSearchIndex();
        });

        static::deleted(function (self $pivot): void {
            Product::query()->find($pivot->product_id)?->syncSearchIndex();
        });
    }
}
