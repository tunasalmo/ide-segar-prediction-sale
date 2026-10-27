<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prediction_runs', function (Blueprint $table) {
            $table->decimal('model_a', 16, 6)->nullable()->after('model_version');
            $table->decimal('model_b', 16, 6)->nullable()->after('model_a');
            $table->unsignedInteger('periods_count')->nullable()->after('model_b');
        });
    }

    public function down(): void
    {
        Schema::table('prediction_runs', function (Blueprint $table) {
            $table->dropColumn(['model_a', 'model_b', 'periods_count']);
        });
    }
};
