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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->string('batch_name');
            $table->string('batch_code')->unique();
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_students')->default(50);
            $table->enum('status', ['planning', 'active', 'completed', 'cancelled'])->default('planning')->index();
            $table->enum('schedule_type', ['daily', 'weekly', 'bi-weekly', 'monthly'])->default('weekly')->nullable();
            $table->json('schedule_days')->nullable(); // ['monday', 'wednesday', 'friday']
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->unique(['tenant_id', 'batch_code']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'is_published']);
        });

        Schema::create('batch_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('batch_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['active', 'completed', 'dropped', 'pending'])->default('pending')->index();
            $table->decimal('progress_percentage', 5, 2)->unsigned()->default(0);
            $table->decimal('final_grade', 5, 2)->unsigned()->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('batch_id')->references('id')->on('batches')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['batch_id', 'student_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_student');
        Schema::dropIfExists('batches');
    }
};
