<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('enrollment_id')->nullable();

            $table->string('paystack_reference')->unique();
            $table->string('paystack_access_code')->nullable();
            $table->string('paystack_authorization_url')->nullable();

            $table->unsignedBigInteger('amount_kobo');       
            $table->unsignedBigInteger('platform_fee_kobo'); 
            $table->unsignedBigInteger('school_amount_kobo');

            $table->string('currency', 3)->default('NGN');
            $table->enum('status', ['pending', 'completed', 'failed', 'abandoned', 'refunded'])
                  ->default('pending')
                  ->index();
            $table->string('channel')->nullable(); 
            $table->string('ip_address', 45)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->json('paystack_response')->nullable(); 
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['student_id', 'batch_id']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id');
            $table->foreign('batch_id')
                  ->references('id')
                  ->on('batches')
                  ->cascadeOnDelete();

            $table->string('enrollment_route')
                  ->default('adult_direct')
                  ->comment('adult_direct | parent_payment');

            $table->unsignedBigInteger('enrollment_payment_id')
                  ->nullable()
                  ->after('enrollment_route');

            $table->foreign('enrollment_payment_id')
                  ->references('id')
                  ->on('enrollment_payments')
                  ->nullOnDelete();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('batch_id');
            $table->string('certificate_code')->unique();
            $table->decimal('final_grade', 5, 2)->unsigned()->nullable();
            $table->string('grade_letter', 3)->nullable();
            $table->unsignedInteger('batch_rank')->nullable();
            $table->unsignedInteger('total_students')->nullable();
            $table->unsignedInteger('attendance_count')->nullable();
            $table->unsignedInteger('total_sessions')->nullable();
            $table->date('issued_at');
            $table->string('issued_by')->nullable(); 
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
            $table->unique(['student_id', 'batch_id']); // one cert per student per batch
            $table->index(['tenant_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropForeign(['enrollment_payment_id']);
            $table->dropForeign(['batch_id']);
            $table->dropColumn(['enrollment_route', 'enrollment_payment_id']);
        });

        Schema::dropIfExists('certificates');
        Schema::dropIfExists('enrollment_payments');
    }
};