<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelEvaluation extends Model
{
    protected $fillable = ['prediction_run_id', 'metric', 'value', 'detail'];

    protected $casts = [
        'value' => 'float',
        'detail' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(PredictionRun::class);
    }
}
