<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['geo_district_id', 'code', 'name'])]
class GeoWard extends Model
{
    use SoftDeletes;

    public function district(): BelongsTo
    {
        return $this->belongsTo(GeoDistrict::class, 'geo_district_id');
    }
}
