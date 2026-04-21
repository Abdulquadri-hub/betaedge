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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('session_type', ['live', 'recorded', 'hybrid'])->default('live');
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->dateTime('actual_start')->nullable();
            $table->dateTime('actual_end')->nullable();
            $table->string('meeting_url')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('meeting_password')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled')->index();
            $table->unsignedInteger('max_participants')->nullable();
            $table->unsignedInteger('actual_participants')->default(0);
            $table->text('recording_url')->nullable();
            $table->unsignedBigInteger('duration_minutes')->nullable();
            $table->json('session_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('instructor_id')->references('id')->on('instructors')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['course_id', 'scheduled_start']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('material_type', ['document', 'video', 'image', 'audio', 'link', 'quiz', 'pdf'])->default('document');
            $table->string('file_url')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_mime_type')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->text('content')->nullable(); 
            $table->integer('display_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_published']);
            $table->index(['course_id', 'display_order']);
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('instructor_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->decimal('max_score', 6, 2)->unsigned()->default(100);
            $table->enum('assignment_type', ['homework', 'project', 'exam', 'quiz', 'essay'])->default('homework');
            $table->boolean('allows_late_submission')->default(true);
            $table->unsignedInteger('late_penalty_percentage')->default(0);
            $table->json('attachments')->nullable();
            $table->enum('status', ['draft', 'active', 'closed', 'archived'])->default('draft')->index();
            $table->boolean('require_file_upload')->default(true);
            $table->boolean('allow_resubmission')->default(false);
            $table->unsignedInteger('max_resubmissions')->default(1);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('instructor_id')->references('id')->on('instructors')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['course_id', 'due_at']);
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('student_id');
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('minutes_late')->default(0);
            $table->enum('status', ['draft', 'submitted', 'graded', 'returned'])->default('draft')->index();
            $table->text('student_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['assignment_id', 'student_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['student_id', 'submitted_at']);
            $table->index(['assignment_id', 'status']);
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('instructor_id');
            $table->decimal('score', 6, 2)->unsigned()->nullable();
            $table->decimal('percentage', 5, 2)->unsigned()->nullable();
            $table->enum('letter_grade', ['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F'])->nullable();
            $table->text('feedback')->nullable();
            $table->json('rubric_scores')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
            $table->foreign('instructor_id')->references('id')->on('instructors')->cascadeOnDelete();
            $table->unique('submission_id');
            $table->index(['tenant_id', 'is_published']);
            $table->index(['instructor_id', 'graded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('class_sessions');
    }
};
