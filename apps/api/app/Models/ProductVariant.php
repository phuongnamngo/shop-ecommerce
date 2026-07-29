<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'code', 'product_id', 'sku', 'barcode', 'price', 'compare_at_price', 'is_default', 'status',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, SoftDeletes;

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
}
