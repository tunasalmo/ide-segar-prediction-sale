<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPrediction extends Model
{
    protected $fillable = [
        'prediction_run_id', 'product_id', 'category_id',
        'period_start', 'period_end', 'predicted_qty', 'predicted_revenue',
        'actual_qty', 'growth_pct', 'score', 'status_label', 'series',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'predicted_qty' => 'float',
        'predicted_revenue' => 'float',
        'actual_qty' => 'float',
        'growth_pct' => 'float',
        'score' => 'float',
        'series' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PredictionRun::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
