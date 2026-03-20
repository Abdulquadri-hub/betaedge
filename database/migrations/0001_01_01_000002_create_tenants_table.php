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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->index();
            $table->string('slug')->unique()->index();
            $table->unsignedBigInteger('owner_id');
            $table->string('owner_email');
            $table->string('custom_domain')->nullable()->unique();
            $table->string('subdomain')->nullable()->unique();
            $table->enum('school_type', ['primary', 'secondary', 'university', 'bootcamp', 'tutoring', 'marketplace'])->default('secondary');
            $table->string('website')->nullable();
            $table->integer('year_established')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#10B981');
            $table->string('timezone')->default('UTC');
            $table->string('currency')->default('USD');
            $table->enum('status', ['active', 'suspended', 'deleted', 'trial'])->default('trial');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('setup_completed')->default(false);
            $table->string('onboarding_step')->nullable();
            $table->json('settings')->nullable(); // Flexible settings storage
            $table->unsignedInteger('max_users')->default(100);
            $table->unsignedInteger('max_courses')->default(50);
            $table->unsignedInteger('max_storage_gb')->default(10);
            $table->unsignedInteger('current_storage_gb')->default(0);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('owner_id')->references('id')->on('users')->restrictOnDelete();
            $table->index('owner_id');
            $table->index('status');
            $table->index('created_at');
            $table->index(['slug', 'status']);
        });

        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['admin', 'manager', 'instructor', 'student', 'parent', 'staff', 'owner'])->default('student');
            $table->json('permissions')->nullable(); // Custom permissions beyond role
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->string('last_accessed_ip')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'role']);
            $table->index('user_id');
        });

        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('email');
            $table->enum('role', ['admin', 'manager', 'instructor', 'student', 'parent'])->default('instructor');
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('invited_by')->nullable();
            $table->unsignedBigInteger('accepted_by')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('invited_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('accepted_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
            $table->index('email');
        });

        Schema::create('domain_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('domain')->unique();
            $table->enum('verification_type', ['dns_txt', 'cname', 'file'])->default('dns_txt');
            $table->string('token')->unique();
            $table->string('expected_value');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by_ip')->nullable();
            $table->integer('verification_attempts')->default(0);
            $table->timestamp('last_verification_attempt_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_verified']);
        });

        Schema::create('tenant_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('page_type', 50); 
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
            
            $table->unique(['tenant_id', 'slug']);
            $table->index('tenant_id');
            $table->index('page_type');
            $table->index('is_active');
        });

        Schema::create('tenant_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('original_name');
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->string('disk')->default('public');
            $table->enum('category', ['document', 'image', 'video', 'audio', 'other'])->default('other');
            $table->string('url')->nullable();
            $table->boolean('is_public')->default(false);
            $table->integer('download_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'category']);
            $table->index('created_at');
        });

        Schema::create('onboarding_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('profile')->nullable();
            $table->json('plan')->nullable();
            $table->json('payment')->nullable();
            $table->enum('status', ['pending', 'failed', 'processing', 'completed', 'processed', 'error', 'draft'])->default('pending')->index();
            $table->text('session_id')->nullable();
            $table->string('current_step', 50)->default('profile');
            $table->tinyInteger('progress_percentage')->default(0);
            $table->string('progress_message')->nullable();
            $table->string('job_id')->unique()->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_processes');
        Schema::dropIfExists('tenant_files');
        Schema::dropIfExists('tenant_pages');
        Schema::dropIfExists('domain_verifications');
        Schema::dropIfExists('tenant_invitations');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenants');
    }
};
