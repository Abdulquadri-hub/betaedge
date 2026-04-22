<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_payment_agreements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('instructor_id');

            $table->enum('payment_type', [
                'per_batch',    // fixed amount when a batch completes
                'per_student',  // fixed amount × enrolled student count
                'monthly',      // flat monthly salary
                'custom',       // free-form arrangement
            ]);

            $table->decimal('amount', 12, 2)->default(0);
            $table->text('payment_terms')->nullable();   // free-text, used for 'custom' especially
            $table->enum('status', ['active', 'inactive'])->default('active')->index();

            $table->timestamps();

            // one active agreement per instructor per tenant
            $table->unique(['instructor_id', 'tenant_id']);

            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->foreign('instructor_id')
                  ->references('id')->on('instructors')
                  ->cascadeOnDelete();

            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_payment_agreements');
    }
};