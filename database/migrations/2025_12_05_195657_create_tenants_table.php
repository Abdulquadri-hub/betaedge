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
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('subdomain')->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('school_type')->nullable(); // academy, tutoring, university, etc
            $table->string('website')->nullable();
            $table->string('year_established')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
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
            $table->string('onboarding_step')->nullable(); // 
            $table->timestamps();
            $table->softDeletes();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_token')->unique()->nullable();
            $table->string('owner_email')->index()->nullable();
            
            $table->index('verification_token');

            $table->index('slug');
            $table->index('custom_domain');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
