<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_batch_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('instructor_id');
            $table->unsignedBigInteger('batch_id');

            $table->decimal('amount_due', 12, 2)->default(0);
            $table->enum('payment_status', ['pending', 'paid'])->default('pending')->index();
            $table->timestamp('marked_paid_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            // one payment record per instructor per batch
            $table->unique(['instructor_id', 'batch_id']);

            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->cascadeOnDelete();

            $table->foreign('instructor_id')
                  ->references('id')->on('instructors')
                  ->cascadeOnDelete();

            $table->foreign('batch_id')
                  ->references('id')->on('batches')
                  ->cascadeOnDelete();

            $table->index(['tenant_id', 'payment_status']);
            $table->index(['instructor_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_batch_payments');
    }
};