<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminCauser;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'code', 'product_id', 'sku', 'barcode', 'price', 'compare_at_price', 'weight_grams', 'is_default', 'status',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, LogsActivity, LogsAdminCauser, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('product_variant')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeOption::class,
            'product_variant_attribute_options',
            'product_variant_id',
            'attribute_option_id'
        )->withPivot('attribute_id')->withTimestamps();
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImage::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (ProductVariant $variant): void {
            self::reindexProduct($variant->product_id);
        });

        static::deleted(function (ProductVariant $variant): void {
            self::reindexProduct($variant->product_id);
        });
    }

    private static function reindexProduct(?int $productId): void
    {
        if ($productId === null) {
            return;
        }

        $product = Product::query()->find($productId);
        if ($product === null) {
            return;
        }

        $product->syncSearchIndex();
    }
}
