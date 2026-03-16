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
        Schema::create('enrollment_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('email')->nullable();
            $table->string('full_name')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('additional_info')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['course_id', 'status']);
        });

        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('from_academic_level_id');
            $table->unsignedBigInteger('to_academic_level_id');
            $table->date('promotion_date');
            $table->enum('promotion_type', ['normal', 'special', 'remedial'])->default('normal');
            $table->decimal('gpa', 3, 2)->unsigned()->nullable();
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('approved')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('promoted_by')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('from_academic_level_id')->references('id')->on('academic_levels')->restrictOnDelete();
            $table->foreign('to_academic_level_id')->references('id')->on('academic_levels')->restrictOnDelete();
            $table->foreign('promoted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['student_id', 'promotion_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
        Schema::dropIfExists('enrollment_requests');
    }
};
