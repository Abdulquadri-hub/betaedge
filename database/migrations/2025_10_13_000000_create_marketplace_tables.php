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
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner_image')->nullable();
            $table->json('featured_courses')->nullable();
            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->decimal('rating', 3, 2)->unsigned()->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_students')->default(0);
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->enum('visibility', ['public', 'private', 'unlisted'])->default('public');
            $table->timestamp('featured_until')->nullable();
            $table->text('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique('tenant_id');
            $table->index(['is_featured', 'is_active']);
            $table->index('country');
        });

        Schema::create('marketplace_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('marketplace_listing_id');
            $table->unsignedBigInteger('reviewer_id');
            $table->unsignedTinyInteger('rating')->comment('1-5 stars');
            $table->text('review_text')->nullable();
            $table->enum('review_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->boolean('is_verified_student')->default(false);
            $table->json('review_categories')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);
            $table->unsignedBigInteger('moderated_by')->nullable();
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('marketplace_listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->foreign('reviewer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('moderated_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['marketplace_listing_id', 'reviewer_id']);
            $table->index(['marketplace_listing_id', 'review_status']);
        });

        Schema::create('marketplace_clicks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('marketplace_listing_id');
            $table->string('ip_address', 45)->nullable();
            $table->string('referrer')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamps();
            
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('marketplace_listing_id')->references('id')->on('marketplace_listings')->cascadeOnDelete();
            $table->index(['marketplace_listing_id', 'created_at']);
            $table->index(['tenant_id', 'clicked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_clicks');
        Schema::dropIfExists('marketplace_reviews');
        Schema::dropIfExists('marketplace_listings');
    }
};
