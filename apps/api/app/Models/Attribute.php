<?php

namespace App\Models;

use Database\Factories\AttributeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'slug', 'position'])]
class Attribute extends Model
{
    /** @use HasFactory<AttributeFactory> */
    use HasFactory, SoftDeletes;

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}
