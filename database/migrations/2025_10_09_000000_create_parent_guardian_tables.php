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
        Schema::create('parents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('parent_id')->unique();
            $table->string('occupation')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->enum('preferred_contact_method', ['email', 'phone', 'sms', 'whatsapp'])->default('email');
            $table->boolean('receives_weekly_report')->default(true);
            $table->boolean('receives_announcements')->default(true);
            $table->json('notification_preferences')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['tenant_id', 'parent_id']);
            $table->index(['tenant_id', 'user_id']);
        });

        Schema::create('student_parent', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('parent_id');
            $table->enum('relationship', ['father', 'mother', 'guardian', 'grandfather', 'grandmother', 'uncle', 'aunt', 'other'])->default('guardian');
            $table->boolean('is_primary_contact')->default(false)->index();
            $table->boolean('can_view_grades')->default(true);
            $table->boolean('can_view_attendance')->default(true);
            $table->boolean('can_view_materials')->default(true);
            $table->boolean('can_view_assignments')->default(true);
            $table->boolean('can_contact_instructor')->default(true);
            $table->timestamp('linked_at')->nullable();
            $table->unsignedBigInteger('linked_by')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('parents')->cascadeOnDelete();
            $table->foreign('linked_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['student_id', 'parent_id']);
            $table->index(['tenant_id', 'is_primary_contact']);
            $table->index(['parent_id', 'is_primary_contact']);
        });

        Schema::create('child_linking_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('student_id');
            $table->enum('relationship', ['father', 'mother', 'guardian', 'grandfather', 'grandmother', 'uncle', 'aunt', 'other'])->default('guardian');
            $table->string('verification_token')->unique();
            $table->text('verification_code')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('parents')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index(['student_id', 'status']);
        });

        Schema::create('parent_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('student_name');
            $table->enum('relationship', ['father', 'mother', 'guardian', 'grandfather', 'grandmother', 'uncle', 'aunt', 'other'])->default('guardian');
            $table->string('verification_token')->unique();
            $table->enum('status', ['pending', 'verified', 'completed', 'expired'])->default('pending')->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_user_id')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('created_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('parent_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('student_id');
            $table->dateTime('last_viewed_at')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('parents')->cascadeOnDelete();
            $table->foreign('assignment_id')->references('id')->on('assignments')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->unique(['parent_id', 'assignment_id', 'student_id']);
            $table->index(['tenant_id', 'is_notified']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_assignments');
        Schema::dropIfExists('parent_registrations');
        Schema::dropIfExists('child_linking_requests');
        Schema::dropIfExists('student_parent');
        Schema::dropIfExists('parents');
    }
};
