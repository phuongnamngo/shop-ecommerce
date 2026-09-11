<?php

namespace App\Models;

use Database\Factories\WarehouseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable(['code', 'name', 'is_default', 'status'])]
class Warehouse extends Model
{
    /** @use HasFactory<WarehouseFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public static function ensureDefault(): self
    {
        $existing = static::query()->where('is_default', true)->first();
        if ($existing !== null) {
            return $existing;
        }

        $warehouse = static::query()->firstOrCreate(
            ['name' => 'Default Warehouse'],
            [
                'code' => (string) Str::ulid(),
                'is_default' => true,
                'status' => 'active',
            ],
        );

        if (! $warehouse->is_default) {
            $warehouse->update(['is_default' => true, 'status' => 'active']);
        }

        return $warehouse->refresh();
    }
}
