<?php

namespace App\Models;

use Database\Factories\AttributeOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['attribute_id', 'code', 'label', 'position'])]
class AttributeOption extends Model
{
    /** @use HasFactory<AttributeOptionFactory> */
    use HasFactory, SoftDeletes;

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
