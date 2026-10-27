<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 40)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('order_date');
            $table->string('status', 32)->default('menunggu_konfirmasi');
            $table->string('payment_status', 32)->default('belum_bayar');
            $table->decimal('total', 14, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['order_date', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
