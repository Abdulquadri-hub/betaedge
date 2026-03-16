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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('class_session_id')->nullable();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present')->index();
            $table->unsignedInteger('minutes_late')->default(0);
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->dateTime('marked_at')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('class_session_id')->references('id')->on('class_sessions')->nullOnDelete();
            $table->foreign('marked_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['student_id', 'course_id', 'attendance_date']);
            $table->index(['tenant_id', 'attendance_date']);
            $table->index(['student_id', 'status']);
            $table->index(['course_id', 'attendance_date']);
        });

        Schema::create('instructor_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('instructor_id');
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'on_leave', 'half_day'])->default('present')->index();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('check_in_location_ip')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('instructor_id')->references('id')->on('instructors')->cascadeOnDelete();
            $table->unique(['instructor_id', 'attendance_date']);
            $table->index(['tenant_id', 'attendance_date']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->enum('report_type', ['student_performance', 'attendance', 'course_analytics', 'financial', 'enrollment', 'instructor_performance'])->default('student_performance');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('filters')->nullable();
            $table->json('data')->nullable();
            $table->string('file_url')->nullable();
            $table->enum('status', ['pending', 'generating', 'ready', 'failed'])->default('pending')->index();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->enum('schedule_frequency', ['once', 'daily', 'weekly', 'monthly'])->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'report_type']);
            $table->index(['tenant_id', 'status']);
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
        Schema::dropIfExists('instructor_attendances');
        Schema::dropIfExists('attendances');
    }
};
