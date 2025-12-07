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
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->string('payment_method'); // card, bank_transfer
            $table->string('payment_provider'); // paystack, flutterwave
            $table->string('provider_payment_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_reference');
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_payments');
    }
};
