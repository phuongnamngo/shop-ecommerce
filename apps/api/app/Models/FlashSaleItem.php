<?php

namespace App\Models;

use Database\Factories\FlashSaleItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['flash_sale_id', 'product_variant_id', 'sale_price', 'qty_cap', 'qty_sold'])]
class FlashSaleItem extends Model
{
    /** @use HasFactory<FlashSaleItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
        ];
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function qtyRemaining(): ?int
    {
        if ($this->qty_cap === null) {
            return null;
        }

        return max(0, (int) $this->qty_cap - (int) $this->qty_sold);
    }
}
