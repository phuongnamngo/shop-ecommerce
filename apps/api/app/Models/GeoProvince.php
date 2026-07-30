<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name'])]
class GeoProvince extends Model
{
    use SoftDeletes;

    public function districts(): HasMany
    {
        return $this->hasMany(GeoDistrict::class);
    }
}
