<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'code', 'name', 'unit', 'price', 'stock', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'float',
        'stock' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(SalesPrediction::class);
    }

    public function latestPrediction(): ?SalesPrediction
    {
        return $this->predictions()
            ->whereHas('run', fn ($q) => $q->where('status', 'completed'))
            ->latest('period_end')
            ->first();
    }
}
