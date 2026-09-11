<?php

namespace App\Models;

use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'type', 'value', 'starts_at', 'ends_at', 'status'])]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory, SoftDeletes;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENTAGE = 'percentage';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(DiscountRule::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }
}
