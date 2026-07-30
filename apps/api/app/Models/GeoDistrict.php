<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['geo_province_id', 'code', 'name'])]
class GeoDistrict extends Model
{
    use SoftDeletes;

    public function province(): BelongsTo
    {
        return $this->belongsTo(GeoProvince::class, 'geo_province_id');
    }

    public function wards(): HasMany
    {
        return $this->hasMany(GeoWard::class);
    }
}
