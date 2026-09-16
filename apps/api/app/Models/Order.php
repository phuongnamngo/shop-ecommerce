<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminCauser;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'number', 'customer_id', 'status', 'currency',
    'subtotal', 'discount_total', 'shipping_total', 'tax_total', 'grand_total',
    'shipping_address_snapshot', 'billing_address_snapshot',
    'shipping_method_id', 'ghn_service_id', 'coupon_id',
    'guest_lookup_token_hash', 'guest_lookup_token_cipher', 'guest_lookup_token_expires_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, LogsActivity, LogsAdminCauser, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('order')
            ->logFillable()
            ->logExcept([
                'guest_lookup_token_hash',
                'guest_lookup_token_cipher',
                'guest_lookup_token_expires_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'shipping_address_snapshot' => 'array',
            'billing_address_snapshot' => 'array',
            'guest_lookup_token_expires_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
