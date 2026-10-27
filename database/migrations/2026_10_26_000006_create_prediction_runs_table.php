<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prediction_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 24)->default('running'); // running | completed | failed
            $table->integer('horizon_days')->default(30);
            $table->date('data_start')->nullable();
            $table->date('data_end')->nullable();
            $table->string('model_version', 40)->default('linreg-weekly-v1');
            $table->string('error', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_runs');
    }
};
