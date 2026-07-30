<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'loyalty_account_id', 'delta', 'reason', 'reference_type', 'reference_id', 'balance_after',
])]
class LoyaltyLedgerEntry extends Model
{
    public function account(): BelongsTo
    {
        return $this->belongsTo(LoyaltyAccount::class, 'loyalty_account_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
