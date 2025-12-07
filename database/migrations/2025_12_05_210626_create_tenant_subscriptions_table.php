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
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->string('subscription_code')->unique();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('current_period_start')->useCurrent();
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('payment_method')->nullable(); // card, bank_transfer
            $table->string('payment_provider')->nullable(); // paystack, flutterwave
            $table->string('provider_subscription_id')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('subscription_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
