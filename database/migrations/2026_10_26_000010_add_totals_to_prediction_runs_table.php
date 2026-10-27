<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prediction_runs', function (Blueprint $table) {
            $table->decimal('predicted_revenue_total', 16, 2)->nullable()->after('horizon_days');
            $table->decimal('predicted_orders_total', 12, 2)->nullable()->after('predicted_revenue_total');
            $table->decimal('accuracy_pct', 8, 2)->nullable()->after('predicted_orders_total');
        });
    }

    public function down(): void
    {
        Schema::table('prediction_runs', function (Blueprint $table) {
            $table->dropColumn(['predicted_revenue_total', 'predicted_orders_total', 'accuracy_pct']);
        });
    }
};
