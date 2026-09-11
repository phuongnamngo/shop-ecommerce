<?php

namespace App\Models;

use App\Jobs\MakeProductSearchable;
use App\Services\Catalog\CatalogProductService;
use App\Support\CatalogSearchDocument;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

#[Fillable([
    'code', 'brand_id', 'name', 'slug', 'status', 'published_at', 'description', 'meta_title', 'meta_description', 'created_by', 'updated_by',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, Searchable, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->using(CategoryProduct::class)
            ->orderBy('categories.id')
            ->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function defaultVariant(): HasOne
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', ProductReview::STATUS_APPROVED);
    }

    public function shouldBeSearchable(): bool
    {
        if ($this->id === null) {
            return false;
        }

        return app(CatalogProductService::class)
            ->applyPublicVisibility(static::query()->whereKey($this->id))
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return CatalogSearchDocument::forProduct($this);
    }

    public function syncSearchIndex(): void
    {
        if ($this->shouldBeSearchable()) {
            $this->searchableSync();

            return;
        }

        $this->unsearchableSync();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            if (
                $product->status === self::STATUS_ACTIVE
                && $product->published_at !== null
                && $product->published_at->isFuture()
            ) {
                MakeProductSearchable::dispatch($product->id)->delay($product->published_at);
            }
        });
    }
}
