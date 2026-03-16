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
        Schema::create('tenant_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->date('analytics_date');
            $table->unsignedInteger('active_users')->default(0);
            $table->unsignedInteger('new_enrollments')->default(0);
            $table->unsignedInteger('completed_assignments')->default(0);
            $table->decimal('average_attendance_percentage', 5, 2)->unsigned()->default(0);
            $table->decimal('total_revenue', 12, 2)->unsigned()->default(0);
            $table->unsignedInteger('total_sessions_held')->default(0);
            $table->decimal('average_student_satisfaction', 3, 2)->unsigned()->default(0);
            $table->unsignedInteger('course_views')->default(0);
            $table->unsignedInteger('student_logins')->default(0);
            $table->json('metrics')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'analytics_date']);
            $table->index('analytics_date');
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event')->nullable()->index();
            $table->string('log_name')->index();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable()->index();
            $table->timestamp('updated_at')->nullable();
            $table->uuid('batch_uuid')->nullable()->index();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['causer_type', 'causer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('tenant_analytics');
    }
};
