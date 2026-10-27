<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionRun extends Model
{
    protected $fillable = [
        'status', 'horizon_days', 'predicted_revenue_total', 'predicted_orders_total',
        'accuracy_pct', 'data_start', 'data_end',
        'model_version', 'error', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'data_start' => 'date',
        'data_end' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function predictions(): HasMany
    {
        return $this->hasMany(SalesPrediction::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(ModelEvaluation::class);
    }
}
