<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'status'])]
class ShippingZone extends Model
{
    use SoftDeletes;

    public function regions(): HasMany
    {
        return $this->hasMany(ShippingZoneRegion::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }
}
