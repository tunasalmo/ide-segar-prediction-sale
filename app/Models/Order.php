<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = [
        'menunggu_konfirmasi' => 'Menunggu konfirmasi',
        'diproses' => 'Diproses',
        'siap' => 'Siap diambil / dikirim',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];

    public const PAYMENT_STATUSES = [
        'lunas' => 'Lunas',
        'dp' => 'DP',
        'belum_bayar' => 'Belum bayar',
    ];

    protected $fillable = [
        'invoice_no', 'customer_id', 'order_date', 'status',
        'payment_status', 'total', 'notes',
    ];

    protected $casts = [
        'order_date' => 'date',
        'total' => 'float',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('invoice_no', 'like', "%{$term}%")
            ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%"));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPaymentLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status] ?? $this->payment_status;
    }
}
