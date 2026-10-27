<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prediction_run_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 24); // MAE | RMSE | MAPE | coverage
            $table->decimal('value', 14, 4)->default(0);
            $table->json('detail')->nullable();
            $table->timestamps();

            $table->index(['prediction_run_id', 'metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_evaluations');
    }
};
