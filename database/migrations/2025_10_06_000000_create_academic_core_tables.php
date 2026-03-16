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
        Schema::create('academic_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name')->index(); // Primary, Secondary JSS, Secondary SS, University, etc.
            $table->string('code')->unique(); # e.g., PRIMARY, SS1, UNIV
            $table->integer('level_number')->index();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('academic_level_id');
            $table->string('course_code')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('level')->nullable();
            $table->unsignedInteger('duration_weeks')->default(12);
            $table->decimal('credit_hours', 5, 2)->unsigned()->default(3);
            $table->decimal('price', 12, 2)->unsigned()->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('learning_objectives')->nullable();
            $table->json('prerequisites')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->unsignedInteger('max_students')->nullable();
            $table->text('syllabus')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('academic_level_id')->references('id')->on('academic_levels')->restrictOnDelete();
            $table->unique(['tenant_id', 'course_code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'academic_level_id']);
        });

        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('instructor_id')->unique();
            $table->string('qualification')->nullable();
            $table->string('specialization')->nullable();
            $table->unsignedInteger('years_of_experience')->default(0);
            $table->text('bio')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->decimal('hourly_rate', 10, 2)->unsigned()->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('part_time');
            $table->date('hire_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'on_leave'])->default('active')->index();
            $table->decimal('average_rating', 3, 2)->unsigned()->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index('user_id');
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('student_id')->unique();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->enum('enrollment_status', ['active', 'inactive', 'graduated', 'withdrawn'])->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('academic_level_id')->nullable();
            $table->decimal('gpa', 3, 2)->unsigned()->default(0);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('academic_level_id')->references('id')->on('academic_levels')->nullOnDelete();
            $table->unique(['tenant_id', 'student_id']);
            $table->index(['tenant_id', 'enrollment_status']);
            $table->index('user_id');
        });

        Schema::create('instructor_course', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('instructor_id');
            $table->timestamp('assigned_date')->nullable();
            $table->boolean('is_primary_instructor')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('instructor_id')->references('id')->on('instructors')->cascadeOnDelete();
            $table->unique(['course_id', 'instructor_id']);
            $table->index(['tenant_id', 'is_primary_instructor']);
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['active', 'completed', 'dropped', 'pending'])->default('active')->index();
            $table->decimal('progress_percentage', 5, 2)->unsigned()->default(0);
            $table->decimal('final_grade', 5, 2)->unsigned()->nullable();
            $table->enum('grade_letter', ['A', 'B', 'C', 'D', 'F'])->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['student_id', 'course_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('instructor_course');
        Schema::dropIfExists('students');
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('academic_levels');
    }
};
