<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create tenants table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('subdomain')->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('school_type')->nullable(); // academy, tutoring, university, etc
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('primary_color')->default('#3B82F6');
            $table->string('secondary_color')->default('#F97316');
            $table->string('timezone')->default('Africa/Lagos');
            $table->string('country')->default('Nigeria');
            $table->string('currency')->default('NGN');
            $table->enum('status', ['active', 'suspended', 'inactive'])->default('active');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('setup_completed')->default(false);
            $table->string('onboarding_step')->nullable(); // profile, structure, instructors, courses, payment
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('custom_domain');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenants');
    }
};


/**
 * Migration: Create tenant_users table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'instructor', 'student', 'parent'])->default('instructor');
            $table->json('permissions')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'role']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_users');
    }
};


/**
 * Migration: Create subscription_plans table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Free, Starter, Professional, Enterprise
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->string('currency')->default('NGN');
            $table->integer('max_students')->default(0); // 0 = unlimited
            $table->integer('max_instructors')->default(0);
            $table->integer('max_courses')->default(0);
            $table->integer('storage_gb')->default(5);
            $table->json('features')->nullable(); // ['custom_domain', 'analytics', 'api_access']
            $table->boolean('has_custom_domain')->default(false);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_api_access')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_plans');
    }
};


/**
 * Migration: Create tenant_subscriptions table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans');
            $table->string('subscription_code')->unique();
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->enum('status', ['active', 'expired', 'cancelled', 'suspended'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('current_period_start')->useCurrent();
            $table->timestamp('current_period_end');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('payment_method')->nullable(); // card, bank_transfer
            $table->string('payment_provider')->nullable(); // paystack, flutterwave
            $table->string('provider_subscription_id')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('subscription_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};


/**
 * Migration: Create tenant_payments table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->nullable()->constrained('tenant_subscriptions');
            $table->string('payment_reference')->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->string('payment_method'); // card, bank_transfer
            $table->string('payment_provider'); // paystack, flutterwave
            $table->string('provider_payment_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_reference');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_payments');
    }
};


/**
 * Migration: Create domain_verifications table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('domain_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('domain');
            $table->string('verification_code');
            $table->json('dns_records')->nullable();
            $table->enum('status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('domain_verifications');
    }
};


/**
 * Migration: Create tenant_invitations table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('email');
            $table->enum('role', ['admin', 'instructor'])->default('instructor');
            $table->foreignId('invited_by')->constrained('users');
            $table->string('invitation_code')->unique();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index('invitation_code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_invitations');
    }
};


/**
 * Migration: Create marketplace_listings table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner_image')->nullable();
            $table->json('featured_courses')->nullable();
            $table->string('category')->nullable(); // primary, secondary, university, tutoring
            $table->json('tags')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->default('Nigeria');
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_students')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->timestamp('featured_until')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
            $table->index('category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_listings');
    }
};


/**
 * Migration: Create marketplace_reviews table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('rating'); // 1-5
            $table->text('review')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(true);
            $table->integer('helpful_count')->default(0);
            $table->integer('reported_count')->default(0);
            $table->timestamps();

            $table->index(['listing_id', 'is_published']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_reviews');
    }
};


/**
 * Migration: Create marketplace_clicks table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('marketplace_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('clicked_at');
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('referrer')->nullable();

            $table->index('listing_id');
            $table->index('clicked_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_clicks');
    }
};


/**
 * Migration: Create tenant_analytics table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->integer('active_students')->default(0);
            $table->integer('active_instructors')->default(0);
            $table->integer('total_enrollments')->default(0);
            $table->integer('new_enrollments')->default(0);
            $table->integer('completed_courses')->default(0);
            $table->decimal('revenue', 10, 2)->default(0);
            $table->integer('class_sessions_held')->default(0);
            $table->decimal('average_attendance_rate', 5, 2)->default(0);
            $table->bigInteger('storage_used_bytes')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'date']);
            $table->index('date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_analytics');
    }
};


/**
 * Migration: Create platform_revenue table
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('platform_revenue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('enrollment_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2);
            $table->decimal('platform_percentage', 5, 2);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency')->default('NGN');
            $table->timestamp('transaction_date');
            $table->enum('status', ['pending', 'completed', 'refunded'])->default('completed');
            $table->timestamps();

            $table->index(['tenant_id', 'transaction_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('platform_revenue');
    }
};


/**
 * Migration: Add tenant_id to existing tables
 */
return new class extends Migration
{
    public function up()
    {
        // Add tenant_id to all tenant-specific tables
        $tables = [
            'students', 'instructors', 'parents', 'courses', 
            'academic_levels', 'enrollments', 'assignments', 
            'class_sessions', 'materials', 'submissions', 
            'attendances', 'grades', 'enrollment_requests',
            'student_promotions', 'payments', 'subscriptions',
            'child_linking_requests', 'parent_assignments',
            'parent_registrations', 'instructor_attendances'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')
                          ->nullable()
                          ->after('id')
                          ->constrained()
                          ->onDelete('cascade');
                    
                    $table->index('tenant_id');
                });
            }
        }
    }

    public function down()
    {
        $tables = [
            'students', 'instructors', 'parents', 'courses', 
            'academic_levels', 'enrollments', 'assignments', 
            'class_sessions', 'materials', 'submissions', 
            'attendances', 'grades', 'enrollment_requests',
            'student_promotions', 'payments', 'subscriptions',
            'child_linking_requests', 'parent_assignments',
            'parent_registrations', 'instructor_attendances'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
