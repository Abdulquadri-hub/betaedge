<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('platform_revenue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('enrollment_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->decimal('platform_percentage', 5, 2);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->timestamp('transaction_date');
            $table->enum('status', ['pending', 'completed', 'refunded'])->default('completed');
            $table->timestamps();

            $table->index(['tenant_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_revenues');
    }
};
