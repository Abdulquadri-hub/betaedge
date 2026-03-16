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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->unsigned();
            $table->string('currency')->default('USD');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
            $table->unsignedInteger('max_courses')->default(10);
            $table->unsignedInteger('max_students')->default(100);
            $table->unsignedInteger('max_instructors')->default(5);
            $table->unsignedInteger('storage_gb')->default(10);
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_custom_domain')->default(false);
            $table->boolean('has_advanced_analytics')->default(false);
            $table->boolean('has_support_priority')->default(false);
            $table->json('features')->nullable();
            $table->decimal('discount_percentage', 5, 2)->unsigned()->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
            
            $table->index('billing_cycle');
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_plan_id');
            $table->string('subscription_code')->unique();
            $table->enum('status', ['active', 'past_due', 'cancelled', 'suspended'])->default('active')->index();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->string('currency')->default('USD');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annual', 'annual'])->default('monthly');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->enum('payment_provider', ['stripe', 'paystack', 'manual'])->default('manual');
            $table->string('provider_subscription_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->restrictOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index('next_billing_date');
        });

        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('subscription_id');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->string('currency')->default('USD');
            $table->enum('payment_method', ['credit_card', 'bank_transfer', 'paystack', 'stripe', 'manual_payment'])->default('manual_payment');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending')->index();
            $table->string('transaction_reference')->nullable();
            $table->string('payment_gateway_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->decimal('refund_amount', 12, 2)->unsigned()->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('refund_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['subscription_id', 'status']);
            $table->index('paid_at');
        });

        Schema::create('tenant_payment_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->enum('payment_provider', ['stripe', 'paystack', 'both'])->default('paystack');
            $table->string('stripe_public_key')->nullable();
            $table->string('stripe_secret_key')->nullable();
            $table->string('stripe_webhook_secret')->nullable();
            $table->string('paystack_public_key')->nullable();
            $table->string('paystack_secret_key')->nullable();
            $table->string('paystack_webhook_secret')->nullable();
            $table->boolean('auto_process_payments')->default(false);
            $table->unsignedInteger('payment_retry_days')->default(3);
            $table->unsignedInteger('max_retry_attempts')->default(3);
            $table->boolean('invoice_email_enabled')->default(true);
            $table->string('invoice_email_from')->nullable();
            $table->json('custom_settings')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique('tenant_id');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->string('currency')->default('USD');
            $table->enum('payment_method', ['manually_paid', 'paystack', 'stripe'])->default('manually_paid');
            $table->string('receipt_path')->nullable();
            $table->string('receipt_filename')->nullable();
            $table->text('parent_notes')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->date('payment_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('parent_id')->references('id')->on('parents')->nullOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('platform_revenues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_payment_id')->nullable();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->string('currency')->default('USD');
            $table->enum('revenue_type', ['subscription', 'commission', 'extra_charge', 'refund'])->default('subscription');
            $table->date('revenue_date');
            $table->json('breakdown')->nullable();
            $table->timestamps();
            
            $table->foreign('subscription_payment_id')->references('id')->on('tenant_payments')->nullOnDelete();
            $table->index(['revenue_date']);
            $table->index(['revenue_type', 'revenue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_revenues');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('tenant_payment_configs');
        Schema::dropIfExists('tenant_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};
