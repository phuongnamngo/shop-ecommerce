<?php

namespace App\Models;

use Database\Factories\FlashSaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable(['code', 'name', 'starts_at', 'ends_at', 'status'])]
class FlashSale extends Model
{
    /** @use HasFactory<FlashSaleFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public const STATUS_CANCELLED = 'cancelled';

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    /**
     * @param  Builder<FlashSale>  $query
     * @return Builder<FlashSale>
     */
    public function scopeEffectiveAt(Builder $query, ?Carbon $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at);
    }
}
