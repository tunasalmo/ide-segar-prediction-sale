<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prediction_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('predicted_qty', 14, 2)->default(0);
            $table->decimal('predicted_revenue', 16, 2)->default(0);
            $table->decimal('actual_qty', 14, 2)->nullable();
            $table->decimal('growth_pct', 8, 2)->nullable();
            $table->decimal('score', 12, 4)->default(0);
            $table->string('status_label', 40)->nullable(); // prioritas_stok | pantau | normal
            $table->json('series')->nullable(); // deret harian qty prediksi, date => qty
            $table->timestamps();

            $table->index(['product_id', 'period_start', 'period_end']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_predictions');
    }
};
